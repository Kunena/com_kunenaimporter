<?php
/**
 * @package com_kunenaimporter
 *
 * Imports forum data into Kunena
 *
 * @Copyright (C) 2009 - 2011 Kunena Team All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 *
 */
defined('_JEXEC') or die();

JHTML::_('behavior.tooltip');
JFactory::getApplication()->enqueueMessage('WARNING: Using this component may cause data losses in your site! Please follow the instructions!', 'notice')
?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
		<td class="kleft" width="55%" valign="top">
			<table id="kunenaimporter" class="adminlist" width="100%">
				<tr>
					<td>
						<h1>1. Choose Your Software</h1>
						<div id="cpanel">
							<h2>Joomla Components</h2>
							<div class="icon-container">
							<div class="icon">
								<a href="index.php?option=com_kunenaimporter&amp;task=start&amp;select=agora&amp;<?php echo JUtility::getToken() ?>=1">
									<?php echo JHTML::_ ( 'image', 'administrator/components/com_kunenaimporter/assets/agora-gray.png', 'Agora' ) ?>
									<span>Agora (experimental)</span>
								</a>
							</div>
							</div>
							<div class="icon-container">
							<div class="icon">
								<a href="index.php?option=com_kunenaimporter&amp;task=start&amp;select=ccboard&amp;<?php echo JUtility::getToken() ?>=1">
									<?php echo JHTML::_ ( 'image', 'administrator/components/com_kunenaimporter/assets/ccboard.png', 'ccBoard' ) ?>
									<span>ccBoard</span>
								</a>
							</div>
							</div>
							<div class="icon-container">
							<div class="icon">
								<a href="index.php?option=com_kunenaimporter&amp;task=start&amp;select=discussions&amp;<?php echo JUtility::getToken() ?>=1">
									<?php echo JHTML::_ ( 'image', 'administrator/components/com_kunenaimporter/assets/discussions-gray.png', 'Discussions' ) ?>
									<span>Discussions (experimental)</span>
								</a>
							</div>
							</div>
							<div class="icon-container">
							<div class="icon">
								<a href="index.php?option=com_kunenaimporter&amp;task=start&amp;select=joobb&amp;<?php echo JUtility::getToken() ?>=1">
									<?php echo JHTML::_ ( 'image', 'administrator/components/com_kunenaimporter/assets/joobb-gray.png', 'Joo!BB' ) ?>
									<span>Joo!BB (experimental)</span>
								</a>
							</div>
							</div>
							<div class="icon-container">
							<div class="icon">
								<a href="index.php?option=com_kunenaimporter&amp;task=start&amp;select=ninjaboard&amp;<?php echo JUtility::getToken() ?>=1">
									<?php echo JHTML::_ ( 'image', 'administrator/components/com_kunenaimporter/assets/ninjaboard-gray.png', 'Ninjaboard' ) ?>
									<span>Ninjaboard (experimental)</span>
								</a>
							</div>
							</div>
							<h2 style="clear:both; padding-top: 10px;">External Software</h2>
							<div class="icon-container">
							<div class="icon">
								<a href="index.php?option=com_kunenaimporter&amp;task=start&amp;select=phpbb2&amp;<?php echo JUtility::getToken() ?>=1">
									<?php echo JHTML::_ ( 'image', 'administrator/components/com_kunenaimporter/assets/phpbb2.png', 'phpBB2' ) ?>
									<span>phpBB2</span>
								</a>
							</div>
							</div>
							<div class="icon-container">
							<div class="icon">
								<a href="index.php?option=com_kunenaimporter&amp;task=start&amp;select=phpbb3&amp;<?php echo JUtility::getToken() ?>=1">
									<?php echo JHTML::_ ( 'image', 'administrator/components/com_kunenaimporter/assets/phpbb3.png', 'phpBB3' ) ?>
									<span>phpBB3</span>
								</a>
							</div>
							</div>
							<div class="icon-container">
							<div class="icon">
								<a href="index.php?option=com_kunenaimporter&amp;task=start&amp;select=pnphpbb2&amp;<?php echo JUtility::getToken() ?>=1">
									<?php echo JHTML::_ ( 'image', 'administrator/components/com_kunenaimporter/assets/pnphpbb2-gray.png', 'PNphpBB2' ) ?>
									<span>PNphpBB2 (experimental)</span>
								</a>
							</div>
							</div>
							<div class="icon-container">
							<div class="icon">
								<a href="index.php?option=com_kunenaimporter&amp;task=start&amp;select=smf2&amp;<?php echo JUtility::getToken() ?>=1">
									<?php echo JHTML::_ ( 'image', 'administrator/components/com_kunenaimporter/assets/smf2-gray.png', 'SMF2' ) ?>
									<span>SMF2 (experimental)</span>
								</a>
							</div>
							</div>
						</div>
					</td>
				</tr>
			</table>
		</td>
		<td width="45%" valign="top">
			<?php echo $this->pane->startPane( 'stat-pane' ) ?>
			<?php echo $this->pane->startPanel( JText::_('Welcome to Kunena Forum Importer'), 'welcome' ) ?>
			<table class="adminlist">
				<tr>
					<td>
						<p>
						<strong>This component allows you to import data from your current forum into Kunena.</strong>
						</p>
						<h3>Instructions:</h3>
						<p>
						<font color="red"><strong>Please follow the instructions to avoid mistakes that could render your site unusable!</strong></font>
						</p>
						<div>
						Before you import:
						<ul>
						<li>Backup your site</li>
						<li>Create test site from backup</li>
						<li>Go to test site</li>
						<li>Install Kunena</li>
						<li>Import your data</li>
						<li>Test everything</li>
						</ul>
						</div>
						<p>
						In this page you can choose your current software - importer supports many Joomla
						and standalone forums. Full list of supported software can be found from the left.
						</p>
						<p>
						Please click the software you want to import from. This action will take you into the
						next page containing more options. <strong>Note:</strong> Going to next page will not start import.
						</p>
						<p>
						<strong>Grayed out</strong> imports are still experimental.
						</p>
					</td>
				</tr>
			</table>
			<?php echo $this->pane->endPanel() ?>
			<?php echo $this->pane->endPane() ?>
		</td>
	</tr>
</table>