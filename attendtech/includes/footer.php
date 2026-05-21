    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script>
    $.fn.dataTable.ext.errMode = 'none';
    $.fn.dataTable.ext.oApi = $.fn.dataTable.ext.oApi || {};
    </script>
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
    <?php if (!empty($extraScripts)) foreach ($extraScripts as $s) echo $s . "\n"; ?>
</body>
</html>
