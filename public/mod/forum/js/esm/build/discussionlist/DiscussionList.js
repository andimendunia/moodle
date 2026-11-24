import{jsx as r}from"react/jsx-runtime";/**
 * Root React component for the forum discussion list.
 *
 * Mounted from mod/forum/view.php via
 * html_writer::react_component('@moodle/lms/mod_forum/discussionlist/DiscussionList', $props).
 *
 * @module     mod_forum/discussionlist/DiscussionList
 * @copyright  2026 Andi Permana <andi.permana@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */function i(s){return r("div",{className:"mod_forum-discussionlist","data-forumid":s.forumid})}export{i as default};
