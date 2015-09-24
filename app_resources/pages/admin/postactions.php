<?php
$page_title = 'Bulk post actions';
translate('<addfile>', 'admin');
//TODO: implement the list of actions that can be done when a bunch topics/posts are checked
//check the validity of the data
if (!isset($_POST['type']) || ($_POST['type'] != 'topics' && $_POST['type'] != 'posts')
	|| ($_POST['type'] == 'posts' && (isset($_POST['form_sent_close']) || isset($_POST['form_sent_open'])))
	|| ($_POST['type'] == 'topics' && (!isset($_POST['topic_action'])))
) {
		httperror(404);
}
if (isset($_POST['form_sent'])) {
	//actually initiate the final action
} else {
	//show a confirmation
	if (isset($_POST['form_sent_close']))
		$action = 'close';
	if (isset($_POST['form_sent_open']))
		$action = 'open';
	if (isset($_POST['form_sent_delete']))
		$action = 'delete';
	if (isset($_POST['form_sent_undelete']))
		$action = 'undelete';
	if (isset($_POST['form_sent_stick']))
		$action = 'stick';
	if (isset($_POST['form_sent_unstick']))
		$action = 'unstick';
	?>
	<h3>Confirm</h3>
	<p>Are you sure you want to <?php echo strtolower(translate($action)); ?> the following <?php echo $_POST['type'] == 'topics' ? translate('topicsp', sizeof($_POST['topic_action'])) : translate('postsp', sizeof($_POST['post_action'])); ?>?</p>
	<?php
}