# Documentation Writing Guide

This guide defines the tone, structure, and style for all Mollie Payments for Magento 2 documentation. Follow it when writing new articles or updating existing ones.

## Audience

The documentation serves two groups:

- **Store owners and administrators** — non-developers who manage a Magento store and need to configure the extension through the Admin UI.
- **Developers** — integrators who install the extension, manage infrastructure, or build custom functionality.

State the intended audience near the top of any page that is clearly aimed at one group but not the other. Example: "This article is for developers managing a multi-store Magento installation."

When a page covers both, lead with the non-developer path and put developer-specific steps in a clearly labelled section.

## Voice and tone

Write in the **second person, active voice**. Address the reader directly as "you". Keep sentences short. Prefer the imperative for instructions.

| Do | Avoid |
|---|---|
| Go to **Stores → Configuration** | The user should navigate to Stores → Configuration |
| Click **Save Config** | Saving can be done by clicking Save Config |
| You can verify this with the self-test | Verification is possible using the self-test |

**Be direct, not terse.** A single sentence of context before a sequence of steps is not padding — it helps the reader confirm they are in the right place. One or two sentences is enough; do not write introductory paragraphs.

**Explain non-obvious steps.** If a step has a consequence the reader might not expect, say so in one sentence. Skip the explanation for steps that are self-evident.

| Do | Avoid |
|---|---|
| Flush the cache after saving — changes to payment methods are not visible to customers until the cache is cleared. | Flush the cache after saving. |
| The self-test also checks webhook reachability, so run it from a publicly accessible server, not localhost. | Run the self-test. |

**Match the gravity of the message to its format.** Use **Important:** for things that can break production or cause data loss. Use **Tip:** for shortcuts and recommendations. Do not label every note.

## Page structure

Every article follows this order:

1. **Title** — `# Article Title`, descriptive and specific. "Credit Card Payments" not "Credit Card".
2. **Opening sentence** — one sentence stating what this page covers and who it is for (if not obvious). Do not restate the title.
3. **Prerequisites** (if any) — a short bulleted list. Skip this section if there are no meaningful prerequisites.
4. **Body** — use `##` headings to divide logical sections. Each section follows the pattern below.
5. **Next steps** — a short list of related articles at the bottom. Use the format already established in existing docs.

### Section pattern

Each `##` section should:

1. Open with one sentence of context (what this setting does or why it matters).
2. Follow with numbered steps for any configuration required.
3. Close with a "what to expect" note if the result is not immediately visible in the UI (optional).

Example:

> ### Enable the Methods API
>
> The methods API filters the payment methods shown at checkout based on the customer's country and cart total, which prevents customers from selecting a method that is not available for their order.
>
> 1. Go to **Stores → Configuration → Sales → Payment Methods → Mollie → Advanced**
> 2. Set **Enable the Methods API** to **Yes**
> 3. Click **Save Config** and flush the cache

## Formatting conventions

**UI paths** use bold with arrows: **Stores → Configuration → Sales → Payment Methods**

**Button and field names** are bold: **Save Config**, **Test API Key**

**Code** (commands, values, file paths, queue names) uses inline code: `composer require mollie/magento2`, `mollie.transaction.processor`

**Code blocks** for multi-line commands or configuration snippets:

```bash
php bin/magento setup:upgrade
php bin/magento cache:flush
```

**Numbered lists** for sequential steps. **Bulleted lists** for non-sequential items (prerequisites, options, features).

Do not use bold for emphasis in running prose. Restructure the sentence instead.

## Language

- Write in British English ("colour", "behaviour", "authorise").
- Spell out abbreviations on first use: "Message Queue (MQ)".
- Use the full product name on first mention in an article: "Mollie Payments for Magento 2". Subsequent references: "the extension" or "Mollie".
- "Magento Admin" for the backend, not "Admin Panel" or "backend".
- Payment method names match Mollie's official capitalisation: iDEAL, Klarna, Apple Pay, Google Pay.

## What to avoid

- Do not use em dashes. Rewrite the sentence or use a comma, colon, or parentheses instead.
- Do not start a page with a heading restating the page title ("## Overview", "## Introduction").
- Do not explain what Magento is or how Composer works — assume basic platform knowledge.
- Do not hedge: "you may want to consider enabling..." → "Enable..."
- Do not repeat steps that are covered in another article. Link to that article instead.
- Do not add a step for "click Save Config" if the preceding numbered list already ends with it.
- Do not write "please".
