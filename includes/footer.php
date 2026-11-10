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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/js/live-search.js"></script>
<script>
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm(btn.getAttribute('data-confirm') || 'Are you sure you want to delete this record?')) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>