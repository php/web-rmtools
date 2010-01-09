<a href="index.php">Back to listing: <?php echo $branch; ?></a>
<form method="post" action="index.php?mode=edit&rev=<?php echo $revision['revision']; ?>" id="topsearch">
	<label>Revision</label>&nbsp;<?php echo $revision['revision']; ?><br />
	<label>Author</label>&nbsp;<?php echo $revision['author']; ?><br />
	<label>Msg</label>&nbsp;<?php echo $revision['msg']; ?><br />
	<label>status</label>&nbsp;<input type="radio" name="status" value="1" <?php echo ($revision['status'] == 1 ? 'checked' : ''); ?>> Merged
	<input type="radio" name="status" value="2" <?php echo ($revision['status'] == 2 ? 'checked' : ''); ?>>Rejected<br />
	<label>Comment</label>
	<textarea name="comment" cols="300"><?php echo $revision['comment']; ?></textarea><br />
	<label>News</label>
	<textarea name="news" class="news"><?php echo $revision['news']; ?></textarea><br />
	<input type="submit" "value="Save"><br />
</form>
<a href="index.php">Back to listing: <?php echo $branch; ?></a>