<div class="wrap">
	<h2><?php echo $this->friendly_name; ?></h2>
	<div id="rage-wrap">
		<div id="y-u-no-header">
			<h4>Options...</h4>
			<img src="<?php echo $this->plugins_url( '/images/y-u-no.png' ); ?>" alt="Y U NO" />
			<h4>Y U NO Has Any?</h4>
		</div>
		<div id="options-instructions">
			<div class="inner">
				<h3>There are no options now, but I have a plan!</h3>
				<p>Here is a list of the options I plan to add to this plugin:</p>
				<ol>
					<li>Store the avatar image paths in the database instead of using the glob() function.</li>
					<li>Enable an interface for selecting which avatars are chosen from the random pool.</li>
					<li>Create a custom-rage-avatars folder outside of the rage-avatars plugin directory for custom avatars.</li>
					<li>Add an option for the randomization. Right now it uses crc32 modding to get the random image so it ensures a user has the same avatar throughout a comment thread. It would be nice to be able to switch it to pure randomness or a cycle mode.</li>
					<li>Remove timthumb dependency. TimThumb is awesome, but it feels like overkill for the simple resizing and caching needed. (maybe, maybe not)</li>
				</ol>
			</div>
		</div>
		<div id="thanks">
			<h3>Thanks to the following sites and users for making this possible... and for all the LOLs.</h3>
			<ul>
				<li>Reddit <a href="http://www.reddit.com/r/fffffffuuuuuuuuuuuu" target="_blank">http://www.reddit.com/r/fffffffuuuuuuuuuuuu</a></li>
				<li>Know Your Meme <a href="http://knowyourmeme.com/memes/rage-comics/children" target="_blank">http://knowyourmeme.com/memes/rage-comics/children</a></li>
				<li>Thanks to <a href="http://kynatro.com/" target="_blank">Kynatro</a> for the sweet WordPress Plugin template.</li>
			</ul>
		</div>
	</div>
</div>