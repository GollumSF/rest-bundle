---
name: Stop asking for permission on safe commands
description: User is frustrated by constantly having to approve safe Docker/bash commands. Just execute them.
type: feedback
---

Stop asking for approval on Docker test runs and safe bash commands. The user wants autonomous execution until tests pass.

**Why:** User said "Execute jusqu'à que le test passe j'en ai marre de dire yes pour rien"
**How to apply:** Chain Docker test commands without waiting for approval. Only ask for permission on destructive operations.
