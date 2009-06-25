<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: u/login.php"); }



// set up
$root_path 			= realpath('./../include')."/";
require_once($root_path . "init.php");

$db = new Database();
$conn = $db->getConnection();

$od = new OwnerDAO();
$owner = $od->getByEmail($_SESSION['user']);

$id = new InstanceDAO();

if ( isset($_REQUEST['u']) && $id->isUserConfigured($_REQUEST['u']) ){
	$username = $_REQUEST['u'];
	$oid = new OwnerInstanceDAO();
	if ( !$oid->doesOwnerHaveAccess($owner->id, $username) ) {
		echo 'Insufficient privileges. <a href="/">Back</a>.';
		$db->closeConnection($conn);
		die;
	} else {
		$i = $id->getByUsername($username);	
	}
} else {
	$i = $id->getFreshestByOwnerId($owner->id);
	if ( !isset($i) && $i != null ) {
		echo 'You have no Twitter accounts configured. <a href="'.$TWITALYTIC_CFG['site_root_path'].'account/">Set up a Twitter account here</a>';
		$db->closeConnection($conn);
		die;
	}
}

$cfg = new Config($i->twitter_username, $i->twitter_user_id);

$s = new SmartyTwitalytic();
$u = new Utils();

// instantiate data access objects
$ud = new UserDAO();
$td = new TweetDAO();

// pass data to smarty
$owner_stats = $ud->getDetails($cfg->twitter_user_id);
$s->assign('owner_stats', $owner_stats);
$s->assign('most_followed_followers', $ud->getMostFollowedFollowers($cfg->twitter_user_id, 15));
$s->assign('least_likely_followers', $ud->getLeastLikelyFollowers($cfg->twitter_user_id, 15));
$s->assign('earliest_joiner_followers', $ud->getEarliestJoinerFollowers($cfg->twitter_user_id, 15));

$s->assign('all_tweets', $td->getAllTweets($cfg->twitter_user_id, 15) );
$s->assign('all_replies', $td->getAllReplies($cfg->twitter_username, 15) );

$s->assign('most_replied_to_tweets', $td->getMostRepliedToTweets($cfg->twitter_user_id, 15));

$s->assign('orphan_replies', $td->getOrphanReplies($cfg->twitter_username, 5));
$s->assign('standalone_replies', $td->getStandaloneReplies());
$s->assign('author_replies', $td->getTweetsAuthorHasRepliedTo($cfg->twitter_user_id, 15));


$s->assign('most_active_friends', $ud->getMostActiveFollowees($cfg->twitter_user_id, 15));
$s->assign('least_active_friends', $ud->getLeastActiveFollowees($cfg->twitter_user_id, 15));
$s->assign('most_followed_friends', $ud->getMostFollowedFollowees($cfg->twitter_user_id, 15));

$s->assign('instance', $i);
$s->assign('instances', $id->getByOwnerId($owner->id));
$s->assign('cfg', $cfg);

//Percentages
$percent_followers_loaded = $u->getPercentage($owner_stats['follower_count'], $i->total_follows_in_system);
$percent_tweets_loaded = $u->getPercentage($owner_stats['tweet_count'],$i->total_tweets_in_system );

$s->assign('percent_followers_loaded', $percent_followers_loaded);
$s->assign('percent_tweets_loaded', $percent_tweets_loaded);

# clean up
$db->closeConnection($conn);	

echo $s->fetch('index.tpl');



/*  People you've gotten the most replies from in the last XXX months (use date of oldest reply)

	select 
		author_username, count(author_user_id) as total_replies 
	from
		reply
	group by
		author_user_id
	order by 
		total_replies desc
	limit 5;
	
	
	People you reply to the most since (date of oldest tweet)
	
		select
			u.user_name, count(t.in_reply_to_user_id) as total_replies
		from
			tweet t
		inner join
			user u
		on
			u.user_id = t.in_reply_to_user_id
		group by
			in_reply_to_user_id
		order by 
			total_replies desc
		limit 10;	
*/




?>