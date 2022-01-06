<?php
/**
 * Block Rendering Template
 */

namespace CUMULUS\Wordpress\Testimonials\Blocks\Slider;

// Exit if accessed directly.
\defined('ABSPATH') || exit('No direct access allowed.');
?>
<div
	class="<?php echo $class ?> splide <?php
        switch ($attr['verticalAlign']) {
            case 'top':
                echo 'splide-align-items-top';
                break;
            case 'bottom':
                echo 'splide-align-items-bottom';
                break;
            case 'center':
            default:
                echo 'splide-align-items-center';
                break;
        }
    ?>"
	data-splide="<?php echo \esc_attr(json_encode($sliderOptions)) ?>"
>
	<div class="splide__track">
		<ul class="splide__list">
			<?php if (count($posts)): ?>
				<?php foreach ($posts as $post): ?>
					<li class="splide__slide">
						<article class="cmls-testimonial <?php echo $class ?>--slide">
							<?php if ($attr['showContent'] === 'full'): ?>
								<div class="body <?php echo $class ?>--content">
									<?php echo \wp_kses_post(\get_the_content(null, false, $post)); ?>
								</div>
							<?php else: ?>
								<div class="body <?php echo $class ?>--content">
									<?php echo \wp_kses_post(\get_the_excerpt($post)); ?>
								</div>
							<?php endif; ?>
							<footer class="<?php echo $class ?>--footer">
								<?php if ($attr['showLogo']): ?>
									<img
										class="<?php echo $class ?>--logo"
										data-splide-lazy="<?php
                                            echo \get_the_post_thumbnail_url($post, 'full')
                                        ?>"
										alt=""
									>
								<?php endif ?>
								<?php
                                    if (
                                        $attr['showCustomerName'] ||
                                        $attr['showCustomerTitle'] ||
                                        $attr['showCompany']
                                    ):
                                ?>
									<div class="meta <?php echo $class ?>--customer">
										<?php if ($attr['showCustomerName']): ?>
											<h3 class="<?php echo $class ?>--customer_name">
												<?php echo \esc_html(strip_tags($post->acf_fields['cmls_testimonial-customer_name'])) ?>
											</h3>
										<?php endif ?>
										<?php if ($attr['showCustomerTitle']): ?>
											<p class="<?php echo $class ?>--customer_title">
												<?php echo \esc_html(strip_tags($post->acf_fields['cmls_testimonial-customer_title'])) ?>
											</p>
										<?php endif ?>
										<?php if ($attr['showCompany']): ?>
											<p class="<?php echo $class ?>--company_name">
												<?php echo \esc_html(strip_tags($post->acf_fields['cmls_testimonial-company_name'])) ?>
											</p>
										<?php endif ?>
									</div>
								<?php endif ?>
							</footer>
						</article>
					</li>
				<?php endforeach ?>
			<?php else: ?>
				<li class="splide__slide">
					No testimonials found!
				</li>
			<?php endif ?>
		</ul>
	</div>
</div>