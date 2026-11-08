<?php if (isLoggedIn()): ?>
    </div>

    <footer class="border-t border-gray-200 bg-white mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-500">
            <p>&copy; <?php echo date('Y'); ?> Agriculture Information System — Cebu Technological University, Cebu City</p>
            <p>Built with PHP + Tailwind CSS + Supabase PostgreSQL</p>
        </div>
    </footer>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm(btn.getAttribute('data-confirm') || 'Are you sure you want to delete this record?')) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>