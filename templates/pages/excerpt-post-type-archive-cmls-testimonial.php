<?php

namespace CUMULUS\Wordpress\Testimonials;

// Exit if accessed directly.
\defined('ABSPATH') || exit('No direct access allowed.');

$acf = \get_fields(\get_the_ID());
?>

<article
	id="post-<?php \the_ID(); ?>"
	<?php \post_class('archive cmls-testimonials'); ?>
>

	<header>
		<?php echo \get_the_post_thumbnail(\get_the_ID(), 'full', ['class' => $Testimonials->name . '--logo' ]); ?>
		<div class="cmls-testimonials--meta">
			<h2>
				<?php echo \esc_html(\strip_tags($acf['cmls_testimonial-customer_name'])); ?>
			</h2>
			<p>
				<?php echo \esc_html(\strip_tags($acf['cmls_testimonial-customer_title'])); ?>
			</p>
			<p>
				<?php echo \esc_html(\strip_tags($acf['cmls_testimonial-company_name'])); ?>
			</p>
		</div>
	</header>


	<div class="body">
		<?php \the_content(); ?>
	</div>
</article>