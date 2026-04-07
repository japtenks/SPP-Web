  </main>

<footer class="site-footer">
  <div class="footer-inner">
    <img src="<?php echo htmlspecialchars(spp_modern_image_url('misc/bot-blizzlogo.gif'), ENT_QUOTES); ?>" alt="Blizzard.com" />
    <div class="footer-text">
      Page generated in <?php echo round($exec_time,4); ?> sec.<br/>
      &copy; <?php echo (string)spp_config_generic('copyright', ''); ?><br/>
      <a href="index.php?n=html&amp;text=license">GNU GPL Licence</a>
    </div>
  </div>
</footer>

<?php
$registeredScripts = spp_render_registered_scripts();
if ($registeredScripts !== ''):
    echo $registeredScripts;
endif;
?>
</body>
</html>
