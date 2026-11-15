<?php if (isLoggedIn()): ?>
    </main>

    <footer class="border-t border-gray-200 bg-white mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row justify-between items-center gap-2 text-xs text-gray-500">
            <p>&copy; <?php echo date(
                'Y',
            ); ?> Agriculture Information System — Cebu Technological University, Cebu City</p>
            <p>PHP + Tailwind CSS + Supabase PostgreSQL</p>
        </div>
    </footer>
<?php else: ?>
    </div>
<?php endif; ?>

<!-- Accessible delete confirmation dialog -->
<div id="deleteDialog" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="deleteDialogTitle" aria-describedby="deleteDialogDesc">
    <div class="fixed inset-0 bg-black/50" id="deleteDialogBackdrop"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6">
            <h3 id="deleteDialogTitle" class="text-lg font-semibold text-gray-900 mb-2">Confirm Deletion</h3>
            <p id="deleteDialogDesc" class="text-sm text-gray-600 mb-6">Are you sure you want to delete this record? This action cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button type="button" id="deleteDialogCancel" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 transition">Cancel</button>
                <button type="button" id="deleteDialogConfirm" class="px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="/js/live-search.js"></script>
<script>
    // Mobile menu toggle
    (function () {
        var btn = document.getElementById('mobileMenuBtn');
        var menu = document.getElementById('mobileMenu');
        if (btn && menu) {
            btn.addEventListener('click', function () {
                var isOpen = !menu.classList.contains('hidden');
                menu.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', String(!isOpen));
                var icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-bars');
                    icon.classList.toggle('fa-xmark');
                }
            });
        }
    })();
</script>
<script>
    // Accessible delete confirmation dialog (replaces browser confirm())
    (function () {
        var dialog = document.getElementById('deleteDialog');
        var backdrop = document.getElementById('deleteDialogBackdrop');
        var cancelBtn = document.getElementById('deleteDialogCancel');
        var confirmBtn = document.getElementById('deleteDialogConfirm');
        var desc = document.getElementById('deleteDialogDesc');
        var pendingForm = null;
        var previousFocus = null;

        function openDialog(message, form) {
            pendingForm = form;
            desc.textContent = message || 'Are you sure you want to delete this record? This action cannot be undone.';
            dialog.classList.remove('hidden');
            previousFocus = document.activeElement;
            confirmBtn.focus();
        }

        function closeDialog() {
            dialog.classList.add('hidden');
            pendingForm = null;
            if (previousFocus) previousFocus.focus();
        }

        document.querySelectorAll('[data-confirm]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openDialog(btn.getAttribute('data-confirm'), btn.closest('form'));
            });
        });

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                if (pendingForm) pendingForm.submit();
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeDialog);
        }
        if (backdrop) {
            backdrop.addEventListener('click', closeDialog);
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !dialog.classList.contains('hidden')) {
                closeDialog();
            }
        });
    })();
</script>
</body>
</html>