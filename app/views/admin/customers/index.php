@layout(admin.layout.header)

<!-- Accounts Section -->
  <div class="bg-white shadow-xl rounded-2xl p-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Customers</h2>
    
    <?php if (empty($customers)): ?>
      <p class="text-gray-500">No customer(s) found.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left border border-gray-200 rounded-lg">
          <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
            <tr>
              <th class="px-4 py-3">Name</th>
              <th class="px-4 py-3">Username</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Role</th>
              <th class="px-4 py-3">Account(s)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php foreach ($customers as $customer): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($customer->name) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($customer->username) ?></td>
                <td class="px-4 py-3"><?php echo ($customer->status == 'pending' ? '<form method="POST" action="/admin/customers/activate"> <input type="hidden" name="id" value="' . $customer->id . '"/> <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-full">Activate</button> </form>' : $customer->status); ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($customer->role) ?></td>
                <td class="px-4 py-3">
                  <?php
                  if ($customer->accounts) {
                    foreach ($customer->accounts as $account) {
                      echo "<div class='text-gray-700'>📝 <a href='/admin/accounts/" . $account->id . "'>" . htmlspecialchars($account->account_number) . "</a> <span class='text-xs text-gray-500'>(" . htmlspecialchars($account->balance) . " | " . $account->status . ")</span></div>";
                    }
                  } else {
                    if($customer->status == 'pending') {
                      echo "<span class='text-gray-400'> - </span>";
                    }else{
                      echo "<span class='text-gray-400'>No accounts</span>";
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

  @layout(admin.layout.footer)
