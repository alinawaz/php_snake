@layout(customer.layout.header)

<!-- Accounts Section -->
<div class="bg-white shadow-xl rounded-2xl p-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Customer Accounts</h2>

    <?php if (empty($accounts)): ?>
        <p class="text-gray-500">No accounts found.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left border border-gray-200 rounded-lg">
                <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Account #</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Balance</th>
                        <th class="px-4 py-3">Cards</th>
                        <th class="px-4 py-3">Apps</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($accounts as $account): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($account->account_number) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($account->type) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($account->status) ?></td>
                            <td class="px-4 py-3 font-semibold text-green-600">$<?= number_format($account->balance, 2) ?></td>
                            <td class="px-4 py-3">
                                <?php
                                if ($account->cards) {
                                    foreach ($account->cards as $card) {
                                        $formatted_card = trim(chunk_split($card->card_number, 4, ' '));
                                        echo "<div class='text-gray-700'>💳 " . htmlspecialchars($formatted_card) . " <span class='text-xs text-gray-500'>(" . htmlspecialchars($card->type) . ")</span></div>";
                                    }
                                } else {
                                    if ($account->status == 'pending') {
                                        echo "<span class='text-gray-400'> - </span>";
                                    } else {
                                        echo "<span class='text-gray-400'> No Cards </span>";
                                    }
                                }
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php
                                if ($account->apps) {
                                    foreach ($account->apps as $app) {
                                        echo "<div class='text-gray-700'>💻 " . htmlspecialchars($app->name);
                                        if (!empty($app->type)) {
                                            echo " <span class='text-xs text-gray-500'>(" . htmlspecialchars($app->type) . ")</span>";
                                        }
                                        echo "</div>";
                                    }
                                } else {
                                    if ($account->status == 'pending') {
                                        echo "<span class='text-gray-400'> - </span>";
                                    } else {
                                        echo "<span class='text-gray-400'> No Apps </span>";
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

@layout(customer.layout.footer)