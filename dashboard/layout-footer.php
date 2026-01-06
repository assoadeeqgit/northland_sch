<?php
/**
 * Layout Footer
 * 
 * Closes the layout structure and includes necessary scripts
 * Only outputs for non-AJAX requests
 */

// Only output layout for non-AJAX requests
if (!isAjaxRequest()):
?>
        </div> <!-- End #main-content -->
        
        <!-- Footer (if you have one) -->
        <?php if (file_exists('footer.php')): ?>
            <?php require_once 'footer.php'; ?>
        <?php endif; ?>
    </main>
    
    <!-- AJAX Navigation Script -->
    <script src="ajax-navigation.js"></script>
    
    <!-- Page-specific scripts (can be added in content pages) -->
    <?php if (isset($pageScripts)): ?>
        <?= $pageScripts ?>
    <?php endif; ?>
</body>
</html>
<?php
endif; // End layout footer
?>
