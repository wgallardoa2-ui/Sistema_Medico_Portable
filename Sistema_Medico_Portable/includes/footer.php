<?php
// includes/footer.php
?>
</main>

<footer class="text-center py-3 text-muted small mt-auto border-top bg-white">
    <div class="container-fluid">
        © <?= date('Y') ?> <strong><?= htmlspecialchars($nombre_clinica ?? 'Clínica Gallardo') ?></strong> — Sistema de Gestión Médica e Historias Clínicas
    </div>
</footer>

<!-- JavaScript Bundle de Bootstrap 5 (Vital para que funcionen los botones y ventanas emergentes) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>