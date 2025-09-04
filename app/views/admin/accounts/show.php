@layout(admin.layout.header)

<!-- Single Account View -->
<div class="bg-white shadow-2xl rounded-2xl p-8 border border-gray-100">
    <!-- Title -->
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold text-[#203a43]">Account Details</h2>
        <a href="/accounts"
            class="inline-flex items-center px-5 py-2.5 rounded-lg shadow-md text-sm font-semibold hover:opacity-90 border border-transparent !text-white"
            style="background-color:#203a43;color: white;">
            <span aria-hidden="true">←</span>
            <span class="ml-2">Back to Accounts</span>
        </a>
    </div>

    <?php if (empty($account)): ?>
        <p class="text-gray-500">Account not found.</p>
    <?php else: ?>
        <!-- Account Info: Proper Grid Layout -->
        <div class="mb-10">
            <h3 class="text-xl font-semibold text-[#203a43] mb-6">General Info</h3>

            <form method="post" action="/admin/accounts/<?php echo $account->id; ?>/status">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <!-- Account Number -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Account #</label>
                        <input type="text" value="<?= htmlspecialchars($account->account_number) ?>"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-gray-700" disabled>
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Type</label>
                        <input type="text" value="<?= htmlspecialchars($account->type) ?>"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-gray-700" disabled>
                    </div>

                    <!-- Status (Editable) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                        <select name="status"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-gray-800 focus:ring-[#203a43] focus:border-[#203a43]">
                            <option value="approved" <?= $account->status === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="pending" <?= $account->status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="suspended" <?= $account->status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>

                    <!-- Balance -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Balance</label>
                        <input type="text" value="$<?= number_format($account->balance, 2) ?>"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-green-600 font-bold" disabled>
                    </div>

                    <!-- Opened On -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Opened On</label>
                        <input type="text" value="<?= htmlspecialchars($account->opened_date ?? 'N/A') ?>"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-gray-700" disabled>
                    </div>

                    <!-- Branch -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Branch</label>
                        <input type="text" value="<?= htmlspecialchars($account->branch ?? 'N/A') ?>"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-gray-700" disabled>
                    </div>

                    <!-- Manager -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Manager</label>
                        <input type="text" value="<?= htmlspecialchars($account->manager ?? 'N/A') ?>"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-100 text-gray-700" disabled>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">&nbsp;</label>
                        <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg shadow-md text-sm font-semibold hover:opacity-90 border border-transparent !text-white"
                            style="background-color:#203a43;color: white;">
                            <span class="ml-2">Update Account</span>
                        </button>
                        <a href="/admin/transactions/create?a=<?php echo $account->account_number; ?>"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg shadow-md text-sm font-semibold hover:opacity-90 border border-transparent !text-white"
                            style="background-color:#203a43;color: white;">
                            <span class="ml-2">Create Transaction</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Cards -->
        <div class="mb-10">
            <h3 class="text-xl font-semibold text-[#203a43] mb-4">💳 Attached Cards</h3>
            <?php if (!empty($account->cards)): ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach ($account->cards as $card): ?>
                        <?php $formatted_card = trim(chunk_split($card->card_number, 4, ' ')); ?>
                        <div class="p-5 rounded-xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 shadow-sm hover:shadow-md transition">
                            <div class="text-lg font-bold text-gray-800"><?= htmlspecialchars($formatted_card) ?></div>
                            <div class="text-sm text-gray-600">
                                Type: <?= htmlspecialchars($card->type) ?> |
                                Exp: <?= htmlspecialchars($card->expiry_month . '/' . $card->expiry_year ?? 'N/A') ?> |
                                CVC: <?= htmlspecialchars($card->cvc ?? 'N/A') ?> |
                                Status: <?= htmlspecialchars($card->status ?? 'N/A') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">
                    No cards linked to this account.
                </p>
                <?php if ($account->status == 'approved'): ?>
                    <form action="/admin/cards/create" method="post">
                        <input type="hidden" name="id" value="<?php echo $account->id; ?>" />
                        <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg shadow-md text-sm font-semibold hover:opacity-90 border border-transparent !text-white"
                            style="background-color:#203a43;color: white;">
                            <span class="ml-2">Create Card</span>
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Apps -->
        <div>
            <h3 class="text-xl font-semibold text-[#203a43] mb-4">💻 Linked Apps</h3>
            <?php if (!empty($account->apps)): ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach ($account->apps as $app): ?>
                        <div class="p-5 rounded-xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 shadow-sm hover:shadow-md transition">
                            <div class="text-lg font-bold text-gray-800"><?= htmlspecialchars($app->name) ?></div>
                            <?php if (!empty($app->type)): ?>
                                <div class="text-sm text-gray-600">Type: <?= htmlspecialchars($app->type) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($app->created_at)): ?>
                                <div class="text-xs text-gray-500">Added: <?= htmlspecialchars($app->created_at) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No apps linked to this account.</p>
                <?php if ($account->status == 'approved'): ?>
                    <form action="/admin/apps/create" method="post">
                        <input type="hidden" name="id" value="<?php echo $account->id; ?>" />
                        <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg shadow-md text-sm font-semibold hover:opacity-90 border border-transparent !text-white"
                            style="background-color:#203a43;color: white;">
                            <span class="ml-2">Create App</span>
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

@layout(admin.layout.footer)