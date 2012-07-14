<?php
/**
 * Kunena Importer component
 * @package Kunena.com_kunenaimporter
 *
 * @copyright (C) 2008 - 2012 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined('_JEXEC') or die();

JHTML::_('behavior.tooltip');
?>

<form action="index.php?option=com_kunenaimporter" method="post" name="adminForm">
	<table class="adminlist" cellpadding="1">
		<thead>
			<tr>
				<th width="2%" class="title">
					<?php echo JText::_( 'NUM' ); ?>
				</th>
				<th width="3%" class="title">
					<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->items); ?>);" />
				</th>
				<th width="1%">
					<?php echo JText::_( 'X' ); ?>
				</th>
				<th width="1%" class="title" nowrap="nowrap">
					<?php echo JHTML::_('grid.sort',   'ID', 'a.id', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
				<th class="title">
					<?php echo JHTML::_('grid.sort',   'Name', 'a.name', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
				<th width="15%" class="title" >
					<?php echo JHTML::_('grid.sort',   'Username', 'a.username', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
				<th width="5%" class="title" nowrap="nowrap">
					<?php echo JHTML::_('grid.sort',   'Enabled', 'a.block', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
				<th width="15%" class="title">
					<?php echo JHTML::_('grid.sort',   'E-Mail', 'a.email', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
				<th width="10%" class="title">
					<?php echo JHTML::_('grid.sort',   'Registered', 'a.registerDate', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
				<th width="10%" class="title">
					<?php echo JHTML::_('grid.sort',   'Last Visit', 'a.lastvisitDate', @$this->lists['order_Dir'], @$this->lists['order'] ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr class="<?php echo "row"; ?>">
			<?php
			$dateformat = version_compare(JVERSION, '1.6', '>') ? 'Y-m-d H:i:s' : '%Y-%m-%d %H:%M:%S';
			$img = $this->user->block ? 'publish_x.png' : 'tick.png';
				$alt = $this->user->block ? JText::_( 'Enabled' ) : JText::_( 'Blocked' );
				if ($this->user->lastvisitDate == "0000-00-00 00:00:00") {
					$lvisit = JText::_( 'Never' );
				} else {
					$lvisit = JHTML::_('date', $this->user->lastvisitDate, $dateformat);
				}
				$rdate = JHTML::_('date', $this->user->registerDate, $dateformat);
			?>
				<td>
					<?php echo '#';?>
				</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>
					<?php echo $this->user->extid; ?>
				</td>
				<td>
					<?php echo $this->user->name; ?>
				</td>
				<td>
					<?php echo $this->user->username; ?>
				</td>
				<td align="center">
					<img src="images/<?php echo $img;?>" width="16" height="16" border="0" alt="<?php echo $alt; ?>" />
				</td>
				<td>
					<a href="mailto:<?php echo $this->user->email; ?>">
						<?php echo $this->user->email; ?></a>
				</td>
				<td nowrap="nowrap">
					<?php echo $rdate; ?>
				</td>
				<td nowrap="nowrap">
					<?php echo $lvisit; ?>
				</td>
			</tr>
			<tr><th colspan="11">Map to Joomla user:</th></tr>
		<?php
			$k = 0;
			$i = 0;
			foreach ($this->items as $row)
			{
				$img = $row->block ? 'publish_x.png' : 'tick.png';
				$task = $row->block ? 'unblock' : 'block';
				$alt = $row->block ? JText::_( 'Enabled' ) : JText::_( 'Blocked' );

				if ($row->lastvisitDate == "0000-00-00 00:00:00") {
					$lvisit = JText::_( 'Never' );
				} else {
					$lvisit = JHTML::_('date', $row->lastvisitDate, $dateformat);
				}
				$rdate = JHTML::_('date', $row->registerDate, $dateformat);
			?>
			<tr class="<?php echo "row$k"; ?>">
				<td>
					<?php echo $i+1;?>
				</td>
				<td>
					<?php echo JHTML::_('grid.id', $i, $row->id ); ?>
				</td>
				<td align="center">
					<?php if ( $row->id == $this->user->id ) : ?>
					<img src="templates/khepri/images/menu/icon-16-default.png" alt="<?php echo JText::_( 'Default' ); ?>" />
					<?php else : ?>
					&nbsp;
					<?php endif; ?>
				</td>
				<td>
					<?php echo $row->id; ?>
				</td>
				<td>
					<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','select')">
						<?php echo $row->name; ?>
					</a>
				</td>
				<td>
					<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','select')">
						<?php echo $row->username; ?>
					</a>
				</td>
				<td align="center">
					<a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>','<?php echo $task;?>')">
						<img src="images/<?php echo $img;?>" width="16" height="16" border="0" alt="<?php echo $alt; ?>" /></a>
				</td>
				<td>
					<a href="mailto:<?php echo $row->email; ?>">
						<?php echo $row->email; ?></a>
				</td>
				<td nowrap="nowrap">
					<?php echo $rdate; ?>
				</td>
				<td nowrap="nowrap">
					<?php echo $lvisit; ?>
				</td>
			</tr>
			<?php
				$k = 1 - $k;
				$i++;
				}
			?>
			<?php if (!$i) : ?>
			<tr>
				<td>
					<?php echo $i+1;?>
				</td>
				<td>
					<?php echo JHTML::_('grid.id', $i, 'NEW' ); ?>
				</td>
				<td></td>
				<td>NEW</td>
				<td>Create new user</td>
				<td colspan="5">&nbsp;</td>
			</tr>
			<?php $i++; endif ?>
			<tr>
				<td>
					<?php echo $i+1;?>
				</td>
				<td>
					<?php echo JHTML::_('grid.id', $i, -1 ); ?>
				</td>
				<td></td>
				<td><input type="text" name="userid" value="" /></td>
				<td>Enter Joomla user ID</td>
				<td colspan="5"><input type="checkbox" name="replace" value="1" /> Replace user profile</td>
			</tr>
		</tbody>
	</table>

	<input type="hidden" name="option" value="com_kunenaimporter" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="view" value="user" />
	<input type="hidden" name="extid" value="<?php echo $this->user->extid ?>" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>