    </div>

    <footer class="bg-light mt-5 py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; 2024 Manajemen Aset Pribadi. Dibuat dengan <i class="fas fa-heart text-danger"></i></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus aset "' + nama + '"?')) {
                // Generate CSRF token untuk delete
                const token = '<?= isset($_SESSION["csrf_token"]) ? $_SESSION["csrf_token"] : "" ?>';
                window.location.href = 'hapus.php?id=' + id + '&token=' + token;
            }
        }
    </script>
</body>
</html>