<div class="allagents-header"<?php echo $header_background_colour != '' ? ' style="background-color:' . $header_background_colour . '"' : ''; ?>>

	<a href="https://www.allagents.co.uk/<?php echo $firm_link; ?>/<?php if ( $branch_link != '' ) { echo $branch_link . '/'; } ?>" target="_blank"><img src="<?php echo $assets_path; ?>/images/allagents_logo.png" class="allagents-logo"></a>

	<div class="allagents-stars">
		<?php
			for ( $i = 1; $i <= 5; ++$i )
			{
				echo '<span>' . ( $stars >= $i ? '★' : '☆' ) . '</span>';
			}
		?>
		<br>
		<div class="allagents-rating">
			<?php echo $rating; ?> out of 5<br>
			based on <?php echo $votes; ?> reviews
		</div>
	</div>

</div>