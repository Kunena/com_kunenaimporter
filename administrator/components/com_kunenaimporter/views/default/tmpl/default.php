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
$disabled = '';
if (!empty($this->errormsg)) $disabled = ' disabled="disabled"';
?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
		<td class="kleft" width="60%" valign="top">
		<form action="index.php" method="post" name="adminForm">
	<input type="hidden" name="option" value="com_kunenaimporter" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="form" value="1" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo JHTML::_( 'form.token' ); ?>

<h1><?php echo JText::sprintf('Import Options for %s', $this->title); ?></h1>

<table class="kunenaimporter">
	<tr valign="top">
		<?php if (!empty($this->params)) : ?>
		<td class="config">
			<?php echo $this->params->render('params'); ?>
		</td>
		<?php endif ?>
	</tr>
</table>

<br />

<table class="adminlist">
	<thead>
		<tr>
		<th class="x" width="1%"><input type="checkbox" name="toggle" value="" <?php echo $disabled; ?> onclick="checkAll(<?php echo count($this->options); ?>);" /></th>
			<th class="title" width="19%"><?php echo JText::_('Task'); ?></th>
			<th class="status" width="10%"><?php echo JText::_('Status'); ?></th>
<!--			<th class="action" width="15%"><?php echo JText::_('Action'); ?></th>-->
			<th class="notes" width="70%"><?php echo JText::_('Description'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td style="text-align: left;" colspan="5">
			&nbsp;
			</td>
		</tr>
	</tfoot>
	<tbody>
<?php if (isset($this->options)): ?>

<?php
$rowNum = 0;
if( $this->options ):
	foreach($this->options as $item=>$option):
		$checked = ($option['status'] < $option['total']) ? 'checked="checked"' : '';

		if ($option['status'] < 0) $statusmsg = '<font color="red">0 / '.$option['total'].'</font>';
		else if ($option['status'] < $option['total']) $statusmsg = '<font color="#b0b000">'.$option['status'].' / '.$option['total'].'</font>';
		else $statusmsg = '<font color="green">'.$option['total'].'</font>';

		$id = '<input type="checkbox" id="cb'.$rowNum.'" name="cid['.$option['name'].']" value="'.intval($option['status'] == $option['total']).'" onclick="isChecked(this.checked);" '.$checked.' />';
?>
		<tr class="row<?php echo $rowNum % 2; ?>">
			<td class="x"><?php echo $id; ?></td>
			<td class="title"><label for="cb<?php echo $rowNum; ?>"><?php echo JText::_($option['task']); ?></label></td>
			<td class="action"><?php echo $statusmsg; ?></td>
			<td class="notes"><?php echo JText::_($option['desc']); ?></td>
		</tr>
<?php
		$rowNum++;
endforeach; ?>
<?php endif; ?>
<?php else: ?>
		<tr><td style="color: red; text-align: left;" colspan="5">Import is currently not possible because of the above errors.</td></tr>
<?php endif; ?>
	</tbody>
</table>
</form>
		</td>
		<td width="40%" valign="top">
			<?php echo $this->pane->startPane( 'stat-pane' ) ?>
			<?php echo $this->pane->startPanel( JText::_('Instructions'), 'welcome' ) ?>
			<table class="adminlist">
				<tr>
					<td>
						<p>
						<font color="red"><strong>Please follow the instructions to avoid mistakes that could render your site unusable!</strong></font>
						</p>
					</td>
				</tr>
			</table>
			<?php echo $this->pane->endPanel() ?>
			<?php echo $this->pane->startPanel( JText::_('Importer Status Information'), 'welcome' ) ?>
			<table class="adminlist">
				<tr>
					<td class="info">
						<?php if( isset($this->messages) ) echo $this->messages; ?>
					</td>
				</tr>
			</table>
			<?php echo $this->pane->endPanel() ?>
			<?php echo $this->pane->endPane() ?>
		</td>
	</tr>
</table>