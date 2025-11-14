    </div>
    
    <footer style="background: var(--dark-color); color: white; padding: 2rem 0; text-align: center; margin-top: 3rem;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Crisis Management System. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="/nov10-crisis/assets/js/main.js"></script>
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
