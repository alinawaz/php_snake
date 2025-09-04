@layout(admin.layout.header)

<!-- Back Button -->
<a href="/accounts" 
   class="inline-flex items-center bg-[#203a43] hover:bg-[#16262b] transition-colors 
          text-white font-semibold px-5 py-2.5 rounded-lg shadow-md text-sm mb-6" style="background-color:#203a43;color: white;">
  ← <span class="ml-2">Back to Accounts</span>
</a>

<!-- Messages -->
<?php if (isset($error)): ?>
  <div class="mb-6 p-4 rounded-lg bg-red-100 text-red-700 font-medium border border-red-300">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<?php if (isset($success)): ?>
  <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-700 font-medium border border-green-300">
    <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>

<!-- Deposit Form -->
<div class="bg-white shadow-xl rounded-2xl p-8">
  <h2 class="text-2xl font-semibold text-gray-800 mb-6">Deposit Cash</h2>

  <form method="POST" action="/admin/transactions/store" class="space-y-8">
    
    <!-- Search Account -->
    <div>
      <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
        Account #
      </label>
      <input type="text" id="search" name="account_number" 
             placeholder="Enter account number" 
             class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-[#203a43] focus:outline-none" value="<?php echo $account_number; ?>" />
    </div>

    <!-- Deposit Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

      <!-- Type -->
      <div>
        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
        <select id="type" name="type" 
                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-[#203a43] focus:outline-none">
          <option value="credit">Credit</option>
          <option value="debit">Debit</option>
        </select>
      </div>

      <!-- Amount -->
      <div>
        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
        <input type="number" id="amount" name="amount" step="0.01" required 
               placeholder="Enter amount" 
               class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-[#203a43] focus:outline-none" />
      </div>

      <!-- Note (full width) -->
      <div class="md:col-span-2">
        <label for="note" class="block text-sm font-medium text-gray-700 mb-2">Note</label>
        <input type="text" id="note" name="note" placeholder="Enter transaction note" 
               class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-[#203a43] focus:outline-none" />
      </div>
    </div>

    <!-- Submit Button -->
    <div>
      <button type="submit"
              class="w-full bg-[#203a43] hover:bg-[#16262b] text-white font-semibold px-6 py-3 rounded-xl shadow-md transition-colors" style="background-color:#203a43;color: white;">
        Create Transaction
      </button>
    </div>
  </form>
</div>

@layout(admin.layout.footer)

