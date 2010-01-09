
<h1>PHP <?php echo $release; ?> Merges</h1>
<table class="listing">
<tr><th>Revision</th><th>Author</th><th>Msg</th><th>Status</th><th>Comment</th><th>News</th><th>&nbsp;</th></tr>
<?php foreach ($svn_log as $r) { ?>
<tr>
<td><a href="http://svn.php.net/viewvc?view=revision&revision=<?php echo $r['revision']; ?>"><?php echo $r['revision']; ?></a></td><td><?php echo $r['author']; ?></td><td><?php echo $r['msg']; ?></td><td><?php if ($r['status'] == 1) echo 'Merged'; elseif ($r['status']== 2) echo "Rejected"; ?></td><td><?php echo $r['comment']; ?></td><td><?php echo $r['news']; ?></td>
<td><a href="index.php?mode=edit&rev=<?php echo $r['revision']; ?>">edit</a></td>
</tr>
<?php } ?>
</table>
