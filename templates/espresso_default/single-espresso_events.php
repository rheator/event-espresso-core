<?php
/**
 * Template Name: Event Details
 *
 * This is template will display a list of your events 
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Seth Shoultes
 * @ copyright		(c) 2008-2013 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link			http://www.eventespresso.com
 * @ version		4+
 */
get_header();
?>

<div class="entry-content maxwidth max-width row">
	<div id="espresso-event-details-wrap-dv" class="site-content">
		<div id="espresso-event-details-dv" class="" role="main">
			<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post();?>
			<?php 
			global $post;
			$wrap_class = '';
			if (has_excerpt( $post->ID )){ $wrap_class .= ' has-excerpt';}
			?>
			<?php do_action( 'AHEE_event_details_before_post', $post ); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class('espresso-event-details'); ?>>
				<?php do_action( 'AHEE_event_details_before_featured_img', $post ); ?>
				<?php				
				if ( has_post_thumbnail( $post->ID )) :
					if ( $img_ID = get_post_thumbnail_id( $post->ID )) :
						if ( $featured_img = wp_get_attachment_image_src( $img_ID, 'large' )) :
							$caption = esc_attr( get_post( get_post( $img_ID ))->post_excerpt );
							$wrap_class .= ' has-img';
							?>
				<div id="ee-event-img-dv-<?php echo $post->ID; ?>" class="ee-event-img-dv">
					<img class="ee-event-img" src="<?php echo $featured_img[0]; ?>" width="<?php echo $featured_img[1]; ?>" height="<?php echo $featured_img[2]; ?>" alt="<?php echo $caption; ?>"/>
				</div>
				<?php 
						endif;
					endif;
				endif;
				?>
				<?php do_action( 'AHEE_event_details_after_featured_img', $post );?>
				<header class="event-header<?php echo $wrap_class;?>">
					<h1 id="event-details-h1">
						<?php the_title(); ?>
					</h1>
					<?php if (has_excerpt( $post->ID )): the_excerpt(); endif;?>
					<p id="event-date-p">
						<?php echo $post->EE_Event->primary_datetime()->start_date_and_time(); ?>
					</p>
				</header>
				<!-- .event-header -->
				
				<div class="espresso-event-wrapper-dv">
					<div class="event-content">
						<h3 class="about-event-h3 ee-event-h3">
							<?php _e( 'Details', 'event_espresso' ); ?>
						</h3>
						<?php do_action( 'AHEE_event_details_before_the_content', $post ); ?>
						<?php the_content(); ?>
						<?php do_action( 'AHEE_event_details_after_the_content', $post ); ?>
						<p>
							<?php the_terms( $post->ID, 'espresso_event_categories', 'Categories: ', ' / ' ); ?>
						</p>
						<h3 class="ticket-selector-h3 ee-event-h3">
							<?php _e( 'Ticket Options', 'event_espresso' ); ?>
						</h3>
						<?php espresso_ticket_selector( $post ); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'event_espresso' ), 'after' => '</div>' ) ); ?>
						<?php if ( espresso_event_phone() != '' ) : ?>
						<p> <strong>
							<?php _e( 'Phone:', 'event_espresso' ); ?>
							</strong> <?php echo espresso_event_phone(); ?> </p>
						<?php endif; ?>
					</div>
					<!-- .event-content -->
					
					<?php do_action( 'AHEE_event_details_before_event_date', $post ); ?>
					<div class="event-datetimes">
						<h3 class="event-datetimes-h3 ee-event-h3">
							<?php _e( 'Date, Time, and Location', 'event_espresso' ); ?>
						</h3>
						<?php espresso_list_of_event_dates();?>
						<?php do_action( 'AHEE_event_details_after_event_date', $post ); ?>
					</div>
					<!-- .event-datetimes -->
					
					<?php if ( espresso_display_venue_address_in_event_details() ) : ?>
					<?php do_action( 'AHEE_event_details_before_venue_details', $post ); ?>
					<div class="espresso-venue-dv">
						<p> <strong>
							<?php _e( 'Location:', 'event_espresso' ); ?>
							</strong><br/>
							<strong>
							<?php espresso_venue_name(); ?>
							</strong> </p>
						<strong>
						<?php _e( 'Address:', 'event_espresso' ); ?>
						</strong>
						<?php espresso_venue_address( 'inline' ); ?>
						<?php espresso_venue_gmap( $post->ID ); ?>
						<div class="clear"><br/>
						</div>
						<p> <strong>
							<?php _e( 'Description:', 'event_espresso' ); ?>
							</strong><br/>
							<?php echo espresso_venue_description(); ?> </p>
						<p> <strong>
							<?php _e( 'Categories:', 'event_espresso' ); ?>
							</strong> <?php echo espresso_venue_categories(); ?> </p>
						<p> <strong>
							<?php _e( 'Phone:', 'event_espresso' ); ?>
							</strong> <?php echo espresso_venue_phone(); ?> </p>
					</div>
					<!-- .espresso-venue-dv -->
					<?php do_action( 'AHEE_event_details_after_venue_details', $post ); ?>
					<?php endif; ?>
					<footer class="event-meta">
						<?php do_action( 'AHEE_event_details_footer_top', $post ); ?>
						<?php espresso_edit_event_link(); ?>
						<?php do_action( 'AHEE_event_details_footer_bottom', $post ); ?>
					</footer>
					<!-- .entry-meta --> 
					
				</div>
			</article>
			<!-- #post -->
			<?php do_action( 'AHEE_event_details_after_post', $post );
			endwhile;
			
			//No events found
			else :?>
			<article id="post-0" class="post no-results not-found">
				<header class="event-header">
					<h1 class="event-title">
						<?php _e( 'The Event you were looking for could not be found...', 'event_espresso' ); ?>
					</h1>
					<br/>
				</header>
				<div class="event-content">
					<p>
						<?php _e( 'Perhaps searching will help find a related event.', 'event_espresso' ); ?>
					</p>
					<?php get_search_form(); ?>
				</div>
				<!-- .event-content --> 
				
			</article>
			<!-- #post-0 -->
			
			<?php endif; // end have_posts() check ?>
		</div>
		<!-- #content --> 
	</div>
	<!-- #primary --> 
	
</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>