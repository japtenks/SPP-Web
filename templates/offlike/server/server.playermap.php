<?php builddiv_start(1, 'Player Map', 1); ?>

<div class="playermap-shell feature-shell">
  <section class="playermap-frame-wrap">
    <iframe
      src="<?php echo htmlspecialchars($playermapFrameSrc); ?>"
      id="playermapFrame"
      class="playermap-frame"
      frameborder="0"
      loading="lazy"
      scrolling="no">
    </iframe>
  </section>
</div>

<?php builddiv_end(); ?>
