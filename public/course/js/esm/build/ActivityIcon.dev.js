var __defProp = Object.defineProperty;
var __name = (target, value) => __defProp(target, "name", { value, configurable: true });
import { jsxDEV } from "react/jsx-dev-runtime";
/**
 * The icon of a course activity card, rendered with the design system ActivityIcon.
 *
 * Mounted from core_courseformat/local/content/cm/cmicon, which keeps a server-rendered
 * fallback inside the mount point until this component takes over. Keep the size of that
 * fallback in sync with this component.
 *
 * @module     core_course/ActivityIcon
 * @copyright  2026 Moodle Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import { ActivityIcon as DSActivityIcon } from "@moodlehq/design-system";
function ActivityIcon({ icon, alt = "", container = "default", size = "md" }) {
  return /* @__PURE__ */ jsxDEV(DSActivityIcon, { icon, alt, container, size }, void 0, false, {
    fileName: "public/course/js/esm/src/ActivityIcon.tsx",
    lineNumber: 48,
    columnNumber: 12
  }, this);
}
__name(ActivityIcon, "ActivityIcon");
export {
  ActivityIcon as default
};
//# sourceMappingURL=ActivityIcon.dev.js.map
