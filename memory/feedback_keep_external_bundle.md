---
name: Keep controller-action-extractor as external bundle
description: User wants to keep gollumsf/controller-action-extractor-bundle as a separate dependency, not inline it. Will migrate it separately later.
type: feedback
---

Do NOT inline gollumsf/controller-action-extractor-bundle into RestBundle. The user has access to the bundle at /home/smeagol/Works/SF/GollumSF/ControllerActionExtractorBundle and will migrate it separately.

**Why:** User prefers to keep packages separate and migrate each one independently.
**How to apply:** When the controller-action-extractor-bundle blocks SF7+ compat, note it as a dependency to update separately rather than copying code into RestBundle.
