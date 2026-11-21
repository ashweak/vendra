# Personal data breach response (draft)

Draft procedure for Vendra and the businesses that use it, based on the Data Privacy Act of 2012 (RA 10173),
its IRR and NPC Circular 16-03. **Not legal advice — have it reviewed before real customer data is used.**

## Roles
- **Business (personal information controller)**: decides on and sends notifications to the NPC and affected
  customers.
- **Vendra (personal information processor)**: tells affected businesses promptly so they can meet the deadline,
  and helps with facts and fixes.

## Steps
1. **Contain** (immediately): revoke leaked credentials and sessions, rotate `APP_KEY`/secrets if exposed, block
   the attack path, keep logs (`audit_logs`, server logs).
2. **Assess** (same day): what data, how many people, whether it is sensitive personal information or other
   information that may enable identity fraud, and whether there is a real risk of serious harm.
3. **Decide on notification**: when the conditions for mandatory notification are met, notify **the NPC and
   affected data subjects within 72 hours** of knowing or reasonably believing a breach happened. Delay is only
   allowed in the limited cases the rules permit; the NPC can be asked for an extension or exemption.
4. **Notify**: what happened, data involved, likely consequences, what was done, what people should do, and a
   contact person.
5. **Record** every incident (even ones not requiring notification): date found, facts, decision and reasons,
   actions taken. Keep for the NPC's annual security incident report.
6. **Review**: fix the root cause and add a test or check so it doesn't happen again.

## Contacts
- National Privacy Commission: https://privacy.gov.ph
- Vendra developer: see `VENDRA_CONTACT_EMAIL` or the GitHub repository.
