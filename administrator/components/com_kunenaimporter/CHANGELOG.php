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
die();
?>
<!--

Changelog
------------
This is a non-exhaustive (but still near complete) changelog for
the Kunena Importer, including beta and release candidate versions.

Legend:

* -> Security Fix
# -> Bug Fix
+ -> Addition
^ -> Change
- -> Removed
! -> Note

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

KunenaImporter 1.6.5-DEV

24-July-2011 Xillibit
# [#25] Make working messages, categories import with ninjaboard

19-July-2011 Xillibit
# [#21] Little issue with parent on agora category import

18-July-2011 Xillibit
# [#21] Improve ccboard and agora support (improve catégories, messages import)

18-July-2011 Matias
^ [#30] Code cleanup in importer & exporter
^ [#5] Reviewed and fixed phpBB3 support, closes #5

17-July-2011 Xillibit
# [#21] Improve ccboard and agora support (avatar galleries import for agora)

17-July-2011 Matias
# [#30] Example exporter: Fix topic subscriptions import
# [#5] phpBB3 support: Fix dates in session import
+ [#30] Example exporter: Add polls support
+ [#37] Add nearly complete support for deprecated phpBB2
^ [#35] Improve usability: Add menu image and missing gray icons, mark unfinished importers as gray

16-July-2011 Matias
^ [#35] Improve usability: Cleaner look
^ [#35] Improve usability: Don't show unused configuration parameters
^ [#35] Improve usability: Select all import options by default (one click import)
# [#35] Improve usability: Truncate selected tables before import (no more white screens on duplicate entries)
^ [#35] Improve usability: Unselect already imported tasks
^ [#35] Improve usability: Changing configuration options should have effect when clicking "Import" or "Truncate"
# [#5] phpBB3 support: Fix various issues with user import
+ [#30] Example exporter: Add missing functions countUsers() and exportUsers()
# [#5] phpBB3 support: Fix database version detection for deprecated phpBB2
+ [#37] Add initial support for deprecated phpBB2

15-July-2011 Matias
+ [#5] phpBB3 and SMF2 support: allow user to create new Joomla users, either manually or automatically

14-July-2011 Xillibit
# [#21] Improve ccboard and agora support (improvements on agora categories import, messages import)
# [#21] Improve ccboard and agora support (improvements on messages import)

14-July-2011 Matias
+ [#35] Improve usability: Nice startup screen with cool icons and usage instructions

13-July-2011 Matias
+ [#27] Full support for ccBoard 1.2-RC
+ [#27] ccBoard: Convert all code to use example exporter as a base
+ [#27] KunenaimporterModelExport: Add HTML to BBCode conversion functions
^ [#27] Importer: Improve and generalize userid mapping (get rid of custom import functions)
# [#27] Importer: Add time delta correction to messages
# [#27] KunenaimporterModelExport: Disable external user conversion for Joomla components
# [#27] KunenaimporterModelExport: Better error detection if export fails
# [#30] example: Fix some issues found while exporting ccBoard
+ [#30] Add default function getConfig() to both example and parent class

12-July-2011 Matias
- [#30] Remove deprecated Kunena exporter
+ [#30] Example exporter: Add functions needed by phpBB3/SMF2
+ [#30] Add missing functions to KunenaimporterModelExport
- [#30] KunenaimporterModelExport: remove the need of buildImportOps()
^ [#30] Move all message generation to KunenaimporterModelExport::detect()
- [#30] Example exporter: remove function buildImportOps()
- [#30] All Joomla exporters: remove functions that are now defined in parent class
^ [#30] phpBB2/3 and SMF2: convert code to follow more closely example exporter
# [#30] Fix Zend warnings

11-July-2011 Matias
+ [#30] Add example exporter which can be used to create new exporters
^ [#30] Rename KunenaimporterModelExport::checkConfig() to detect()
+ [#30] Add function KunenaimporterModelExport::detectComponent()
+ [#30] Add function KunenaimporterModelExport::isCompatible()
^ [#30] Update exportConfig() for all existing exporters
^ [#30] Convert detection code from all existing exporters

11-July-2011 Xillibit
# [#21] Improve ccboard and agora support (ccboard categories import)

10-July-2011 fxstein
^ [#4] Update file headers, remove $Id, 2010->2011 and kunena.com->.org
+ [#25] Add basic skeleton for Ninjaboard support
+ [#26] Enable Agora options
+ [#27] Enable ccBoard options

10-July-2011 Matias
^ [#20] Add support for Joomla 1.7: Code cleanup, DS removal and small fixes
^ [#20] Add support for Joomla 1.7: Convert language strings to the new format
^ [#20] Add new option - Select Forum - and use default exporter with failing message
^ [#20] Add configuration fields for Joomla 1.7
^ [#21] Improve ccboard and agora support: fix 2 typos

10-July-2011 Xillibit
# [#18] Notice: Undefined property: KunenaimporterViewDefault::$options in \views\default\tmpl\default.php on line 47
^ [#21] Improve ccboard and agora support

9-July-2011 Matias
# [#5] phpBB3 support: Detect that php3 directory exists
+ [#5] phpBB3 support: Remove dependence to rokBridge by adding missing functions
+ [#5] phpBB3 support: Add automatic user mapping
+ [#17] Enable SMF2 support
# [#17] SMF2 support: Fix broken configuration import
+ [#17] SMF2 support: Import avatar galleries

8-July-2011 Matias
+ [#5] phpBB3 support: Store passwords with some metadata (phpbb2/phpbb3)
^ [#5] phpBB3 support: Change database import order
^ [#5] phpBB3 support: Improve component parameter fetch
^ [#5] phpBB3 support: Make user import view easier to use

8-July-2011 fxstein
+ [#8] Basic skelleton for KunenaImporter authentication plugin
# [#3] Update builder for new README.md
^ [#11] Update version info to 1.6.5
+ [#13] Add new flag to user migration table
+ [#12] Add basic version check for Kunena and disable if not present
# [#14] Missing forum table prefix re-added

7-July-2011 Matias
+ [#5] phpBB3 support: migrate uploaded avatars and gallery

6-July-2011 Matias
+ [#5] phpBB3 support: migrate attachments

5-July-2011 Matias
^ [#1] Convert component to PHP5

29-November-2011 Xillibit
# [#20178] Fixes some issues with ccBoard importer

30-October-2011 svens LDA
^ [#22975] update ru-RU (thanks ZARKOS)

30-October-2011 fxstein
^ [#20178] Project Cleanup - All validation warnings and errors fixed

16-September-2011 Matias
+ [#20178] Added ru-RU (thanks ZARGOS)

6-September-2011 Matias
# [#20178] User mapping: Misc fixes and optimizations
# [#20178] User mapping: Update all user information when user gets mapped manually (no need to re-run importer)
# [#20178] User mapping: Map user by manually entering id, unmap by 0

4-September-2011 Matias
# [#20178] User mapping: Greatly improve performance by making own task from basic user mapping
# [#20178] User mapping: Improve performance by preloading needed user mapping when migrating tables
# [#20178] User mapping: Better user mapping reports
# [#20178] User mapping: Improve algorithm to map users who have slightly different information in Joomla
# [#20178] User: Fix last logged in info if user has posts, but has never logged in (lost during previous migration?)
+ [#20178] User mapping: Add filters by ignored users and never logged in

3-September-2011 Matias
# [#20178] SMF2: Fix subscriptions export
# [#20178] Import: Map all userids from external to Joomla ids
# [#20178] Import: Use negative userids if user isn't mapped (allows late mapping)
# [#20178] User mapping: Fatal error, trying to access protected variable
# [#20178] User mapping: Fix pagination
# [#20178] User mapping: Filter by mapped/unmapped/all

2-September-2011 Matias
# [#20178] SMF2: Better version detection
# [#20178] SMF2: Import some configuration options
# [#20178] SMF2: Cleanup text on categories, messages

1-September-2011 Matias
+ [#20178] Create new exporter for SMF2 (standalone)
# [#20178] phpBB3: Fix subscriptions export
- [#20178] Remove database options from configuration

KunenaImporter 1.6.0-RC1

24-August-2011 Matias
^ [#20178] phpBB3: Update exporter for Kunena 1.6 (add some missing fields, do not add slashes)
+ [#20178] phpBB3: Use rokbridge to map users between phpBB and Joomla (most reliable way)
# [#20178] phpBB3: Use right field for username if migrated from SMF
^ [#20178] Minimum Kunena version requirement: 1.6.0RC2 build 3251

22-August-2011 Matias
^ [#20178] Cleanup all files, remove empty directories, files, improve installer etc

16-August-2011 Xillibit
+ [#20178] Support partial for ccboard and agora

22-Apr-2011 Matias
# [#20178] Fix [url] tag (contained extra information and were not parsed)
# [#20178] Some posts were modified 0 minutes ago
# [#20178] Importer status was broken (showing 0% all the time)
# [#20178] Modified by (userid) was not mapped to Joomla

21-Apr-2011 Matias
# [#20178] Add views to aid on users import
# [#20178] phpBB3: Fix slashes on category names
# [#20178] phpBB3: Fix never logged in date
# [#20178] Fix installer: usermap table was not created

19-Apr-2011 Matias
+ [#20178] Detect rokbridge (phpBB3)

17-Apr-2011 Matias
# [#20178] Make more stable against database errors, including failed detection of external forum
# [#20178] Detect and prevent importing Kunena into itself

03-Apr-2011 Matias
# [#20178] Restructuring, add build system, add keywords for every file etc

30-Aug-2009 Matias
# [#17875] Fix all errors and warnings found in Eclipse

4-Aug-2009 Matias
+ [#17485] Initial version of Kunena Importer

 -->
