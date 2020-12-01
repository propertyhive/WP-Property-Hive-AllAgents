<div class="allagents-reviews">

	<?php
		foreach ( $reviews as $review )
		{
	?>
	<div class="allagents-review">

		<div class="allagents-stars">
			<?php
				for ( $i = 1; $i <= 5; ++$i )
				{
					echo '<span>' . ( isset($review->rating) && $review->rating >= $i ? '★' : '☆' ) . '</span>';
				}
			?>
		</div>

		<div class="allagents-date">
			<?php
				echo isset($review->date_added) ? date("jS F Y", strtotime($review->date_added)) : '';
			?>
		</div>

		<div class="allagents-reviewer">
			<?php
				echo isset($review->name) ? stripslashes($review->name) . ' said:' : '';
			?>
		</div>

		<div class="allagents-quote">
			<?php
				echo isset($review->review) ? stripslashes($review->review) : '';
			?>
		</div>

		

	</div>
	<?php
		}
	?>

</div>