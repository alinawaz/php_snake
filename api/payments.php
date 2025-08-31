<?php
require "../config.php"; // mysqli or pdo connection


/*
Params: 
?app_id=APP123&secret=123&amount=100.50&redirect_url=https://host.com/callback&card_token=xxxx
*/

$app_id = $_GET['app_id'] ?? '';
$secret = $_GET['secret'] ?? '';
$amount = $_GET['amount'] ?? 0;
$order_id = $_GET['order_id'] ?? 0;
$status = "success"; // default success, can be changed to failed
$card = null;

$redirect_url = $_GET['redirect_url'] ?? '';
$card_token = $_GET['card_token'] ?? null;

if (!$app_id || !$secret || !$amount || !$redirect_url) {
    die("Invalid request.");
}

$stmt = $conn->prepare("SELECT * FROM apps WHERE `name`=? AND `secret_key` = ? LIMIT 1");
$stmt->bind_param("ss", $app_id, $secret);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();

if (!$app) {
    die("Invalid app credentials.");
}

// Check for app permissions
$permissions = json_decode($app['permissions'], true);
if (!in_array('card_payments', $permissions)) {
    die("App not authorized for card payments.");
}

// If card token provided, fetch card
if ($card_token) {
    $stmt = $conn->prepare("SELECT c.* FROM card_tokens t JOIN cards c ON t.card_id=c.id WHERE t.token=?");
    $stmt->bind_param("s", $card_token);
    $stmt->execute();
    $card = $stmt->get_result()->fetch_assoc();
}


// Pay Now button clicked
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['pay'])) {

        // Using saved card
        if (isset($_POST['card_id']) && is_numeric($_POST['card_id']) && $card_token) {

            if ($card_token) {

                if ($card && $card['status'] == 'approved') {

                    // Charge card (simulate)
                    $account_id = chargeCard($card, $amount, $app);

                    if ($account_id > 0) {
                        // Credits to app owner's account (simulate)
                        creditAppOwner($app, $amount, $account_id);
                    } else {
                        die("Payment failed during charging.");
                    }
                } else {
                    die("Card not approved or invalid.");
                }

                header("Location: $redirect_url?status=$status&order_id=$order_id");
                exit;
            }
        } else {

            // Process payment
            $card_number = $_POST['card_number'] ?? '';
            $card_number = trim(str_replace(' ', '', $card_number));
            $exp = $_POST['exp'] ?? '';
            $cvc = $_POST['cvc'] ?? '';
            $holder = $_POST['holder'] ?? '';
            $exp_month = explode('/', $exp)[0] ?? '';
            $exp_year = explode('/', $exp)[1] ?? '';

            $stmt = $conn->prepare("SELECT * FROM cards WHERE card_number=? AND expiry_month=? AND expiry_year=? AND cvc=? LIMIT 1");
            $stmt->bind_param("siis", $card_number, $exp_month, $exp_year, $cvc);

            $stmt->execute();
            $card = $stmt->get_result()->fetch_assoc();

            if (!$card) {
                die("Invalid card details.");
            }

            if ($card && $card['status'] == 'approved') {

                // Charge card (simulate)
                $account_id = chargeCard($card, $amount, $app);

                if ($account_id > 0) {
                    // Credits to app owner's account (simulate)
                    creditAppOwner($app, $amount, $account_id);
                } else {
                    die("Payment failed during charging.");
                }

            } else {
                die("Card not approved or invalid.");
            }
        }



        // If "save card" checked and no token exists
        if (isset($_POST['save_card'])) {
            if (!in_array('card_captures', $permissions)) {
                die("App not authorized for card captures while you checked card save.");
            }

            $token = bin2hex(random_bytes(16));
            $card_id = intval($card['id']);
            $user_id = intval($app['user_id']);
            $stmt = $conn->prepare("INSERT INTO card_tokens (user_id, card_id, token) VALUES (?,?,?)");
            $stmt->bind_param("iis", $user_id, $card_id, $token);
            $stmt->execute();
            header("Location: $redirect_url?status=$status&card_token=$token&order_id=$order_id");
            exit;
        }

        header("Location: $redirect_url?status=$status&order_id=$order_id");
        exit;
    } elseif (isset($_POST['cancel'])) {
        header("Location: $redirect_url?status=failed");
        exit;
    }
}

function chargeCard($card, $amount, $app)
{
    global $conn;
    // Find associated account with card
    $account_id = $card['account_id'];
    $account = $conn->query("SELECT * FROM accounts WHERE id=$account_id LIMIT 1")->fetch_assoc();
    if (!$account) {
        die("No account found for this card.");
    }

    // Checking available funds
    $new_balance = $account['balance'] - $amount;
    if ($new_balance < 0) {
        die("Insufficient funds.");
    }

    // Charge card (simulate)
    $stmt = $conn->prepare("INSERT INTO transactions (`account_id`, `type`, `amount`, `message`, `status`) VALUES (" . $account['id'] . ", 'debit', " . $amount . ", 'Card charged via " . $app['name'] . ".', 'charged')");
    $stmt->execute();

    // Update connected account balance of card
    $stmt = $conn->prepare("UPDATE accounts SET balance=? WHERE id=?");
    $stmt->bind_param("di", $new_balance, $account['id']);
    $stmt->execute();

    if ($stmt->affected_rows == 0) {
        return 0;
    } else {
        // Payment success simulation
        return $account['id']; // return account ID as success indicator
    }
}

function creditAppOwner($app, $amount, $returned_account_id)
{
    global $conn;
    // Find app owner
    $owner_id = $app['user_id'];
    $owner_account = $conn->query("SELECT * FROM accounts WHERE user_id=$owner_id LIMIT 1")->fetch_assoc();
    if (!$owner_account) {
        die("No account found for app owner.");
    }

    // Credit owner's account
    // $new_balance = $owner_account['balance'] + $amount;
    // $stmt = $conn->prepare("UPDATE accounts SET balance=? WHERE id=?");
    // $stmt->bind_param("di", $new_balance, $owner_account['id']);
    // $stmt->execute();
    // *** This will be done once transaction is approved/charged by admin ***

    // Insert transaction record
    $stmt = $conn->prepare("INSERT INTO transactions (`account_id`, `returned_account_id`, `type`, `amount`, `message`) VALUES (" . $owner_account['id'] . ", " . $returned_account_id . ", 'credit', " . $amount . ", 'Earnings from app " . $app['name'] . ".')");
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Bank Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f4f4f4;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            margin: auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .1);
        }

        .field {
            margin-bottom: 15px;
        }

        .field label {
            display: block;
            margin-bottom: 5px;
        }

        .field input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary {
            background: #007bff;
            color: #fff;
        }

        .btn-danger {
            background: #dc3545;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Complete Payment</h2>
        <p><b>Amount:</b> $<?= number_format($amount, 2) ?></p>

        <?php if ($card): ?>
            <p><b>Using Saved Card:</b> **** **** **** <?= substr($card['card_number'], -4) ?></p>
            <form method="post">
                <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                <button type="submit" name="pay" class="btn-primary">Pay</button>
                <button type="submit" name="cancel" class="btn-danger">Cancel</button>
            </form>
        <?php else: ?>
            <form method="post">
                <div class="field">
                    <label>Card Number</label>
                    <input type="text" name="card_number" required>
                </div>
                <div class="field">
                    <label>Expiry (MM/YY)</label>
                    <input type="text" name="exp" required>
                </div>
                <div class="field">
                    <label>CVC</label>
                    <input type="text" name="cvc" required>
                </div>
                <div class="field">
                    <label>Card Holder Name</label>
                    <input type="text" name="holder" required>
                </div>
                <div class="field">
                    <label><input type="checkbox" name="save_card"> Save this card for future payments</label>
                </div>
                <button type="submit" name="pay" class="btn-primary">Pay</button>
                <button type="submit" name="cancel" class="btn-danger">Cancel</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>