	</div><!-- end .wrap -->
</div><!-- end .main-container -->

<footer class="footer">
	<div class="footer__wrap wrap">
		<div class="footer__content">
			<?php echo Theme::nav( 'footer' ); ?>
			<p class="footer__copyright">&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>.</p>
		</div>
	</div>
</footer>

</div><!-- end .body-overflow -->

<section id="wp-footer">
	<?php wp_footer(); ?>
</section>

</body>
</html>
