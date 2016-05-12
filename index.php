<?php

/* Catch-all templates */

get_header();

?>

<div class="main-content">
	<?php while ( have_posts() ) : the_post(); ?>

		<article id="<?php echo get_post_type(); ?>-<?php the_ID(); ?>" <?php post_class(); ?> role="article">
			<div class="entry-header">
				<h1 class="entry-title">
					<?php the_title(); ?>
				</h1>
				<?php if ( ! is_page() ) : ?>
					<p class="entry-byline">
						<?php
							$categories = ( has_category() ) ? ' <span class="entry-byline-amp">&</span> filed under %4$s' : '';
							printf(
								__( 'Posted <time class="entry-updated" datetime="%1$s" pubdate>%2$s</time> by <span class="entry-author">%3$s</span>' . $categories . '.', THEME_TEXT_DOMAIN ),
								get_the_time( 'Y-m-j' ),
								get_the_time( get_option( 'date_format' ) ),
								get_the_author(),
								get_the_category_list( ', ' )
							);
						?>
					</p>
				<?php endif; ?>
			</div>

			<div class="entry-content clearfix" itemprop="articleBody">
				<?php the_content(); ?>
			</div>

			<div class="entry-footer">
				<p class="entry-tags">
					<?php the_tags( '<span class="entry-tags-label">' . __( 'Tags:' ) . '</span> ', ', ', '' ); ?>
				</p>
			</div>

			<?php posts_nav_link(); ?>
			<?php comments_template(); ?>
		</article>

	<?php endwhile; ?>
</div>

<?php

get_sidebar();
get_footer();
