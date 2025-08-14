# OJS 3.4+ Web of Science Reviewer Recognition Service Plugin

Developed and maintained by: Clarivate with the support from PLANet Systems Group.

### About
This plugin provides the ability to send and publish reviews to Web of Science Researcher Profiles (formerly known as Publons, https://webofscience.com) from OJS hosted journals.

### License
This plugin is licensed under the GNU General Public License v3.

### System Requirements
- OJS 3.4 - 3.5
- CURL support for PHP.
- ZipArchive support for PHP.

### Installation
Option #1 (from OJS plugin gallery)
 - On your OJS site go to Settings > Website > Plugins > Plugin Gallery
 - Search for "Web of Science Reviewer Recognition" plugin and if found, install

Option #2 (when not available via OJS plugin gallery)
 - Download the `tar.gz` plugin file for your OJS version from https://github.com/clarivate/wos_reviewer_recognition_plugin_ojs_3/releases
 - On your OJS site go to Settings > Website > Plugins > Upload a New Plugin, select the file you downloaded and click "Save"

Enable and configure:
 - Enable the plugin by going to:  Settings > Website > Plugins > Installed Plugins and ticking "ENABLE" for the "Web of Science Reviewer Recognition Service Plugin"
 - Set up correct credentials to post reviews to Web of Science in the "Connection" tab under plugin
   - Enter the Authorization Token of the Web of Science Researcher Profile user <b>who has API access to Reviewer Recognition Service</b>. Authorization Token can be found here: https://publons.com/api/v2 (note: you need to be logged in to see this).
   - Enter the Journal Token provided by Clarivate

### Usage
For the plugin to work, the journal should be an official partner of Web of Science Reviewer Recognition Service. Please see information about purchasing this service [here](https://clarivate.com/products/scientific-and-academic-research/research-publishing-solutions/reviewer-recognition-service/), or <a href="mailto:reviewservices@clarivate.com">email us</a> to start giving your reviewers recognition.


When the plugin is enabled, a button "Export my review to Web of Science" will be present on "Completed" tab after the reviewer has submitted their review. After the reviewer has clicked on this button and confirmed they want to send their review to Web of Science, the review data is sent automatically and reviewer receives an invitation to claim it (or it is automatically added if reviewer has Web of Science Researcher Profile and opted in to automatically add reviews from partnered journals).
The Web of Science website certifies only the fact the reviewer has completed peer review for the current journal. The text of the review can be disclosed on Web of Science only after publication of the article and if both the publication author and journal allow it. To disclose the text of the review, the reviewer should input the DOI of the published article on Web of Science.

### Contact
For enquiries regarding usage, support, bugfixes, or comments please email:
reviewservices@clarivate.com
