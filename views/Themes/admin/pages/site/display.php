<?php

require_once 'incs/init.php';

echo '<div id="mainContainer" data-plugins="main">';
	if( $this->count_nav > 1 ){ require_once 'incs/left.php'; }

	echo '<div role="content"><div role="main"><div class="pal mal">';
		
		echo '<div id="site-settings" class="admin-settings">';
			if( !empty($this->section) ){
				require_once "sections/{$this->section}.php";
			}
		echo '</div>';

		// 
	echo '</div></div></div>';

echo '</div>';