<?php
namespace CUMULUS\Wordpress\Testimonials;
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

$acf = \get_fields(\get_the_ID());
?>

<article
	id="post-<?php \the_ID() ?>"
	<?php \post_class( 'archive cmls-testimonials') ?>
>
	<div class="body">
		<?php \the_content() ?>
	</div>
	<footer>
		<?php echo \get_the_post_thumbnail(\get_the_ID(), 'full', array('class' => 'cmls-testimonials--logo')) ?>
		<div class="cmls-testimonials--meta">
			<h3>
				<?php echo \esc_html(strip_tags($acf['cmls_testimonial-customer_name'])) ?>
			</h3>
			<p>
				<?php echo \esc_html(strip_tags($acf['cmls_testimonial-customer_title'])) ?>
			</p>
			<p>
				<?php echo \esc_html(strip_tags($acf['cmls_testimonial-company_name'])) ?>
			</p>
		</div>
	</footer>
</article>