# LYVO — User Journeys

> Plain-language walkthroughs of what people see and do on LYVO. Each feature added
> to the platform gets a short journey here. Technical details live in
> [DevDocs/authentication.md](../DevDocs/authentication.md).

---

## Authentication

LYVO is a trust-based marketplace. Before anyone can buy, sell or manage the
platform, they create one account. The kind of account — **Customer**, **Operator**
(seller) or **Admin** — decides what they can see and do. To keep everyone safe, every
account verifies both an **email address** and a **phone number**, and every seller is
**reviewed by a LYVO admin** before they can trade.

### Who is who

| Role | Who they are | Where they land |
|------|--------------|-----------------|
| **Customer** | Someone who buys from verified operators | Customer dashboard |
| **Operator** | A verified seller / business | Operator dashboard (after approval) |
| **Admin** | The LYVO team reviewing sellers and the platform | Admin dashboard |
| **Guest** | A visitor browsing without an account | Public pages only |

---

### Journey 1 — Customer sign-up

1. Visit **Register** and enter name, email, phone and a password.
2. The account is created and you're signed in straight away.
3. LYVO sends a 6-digit code to your **email** and another to your **phone**.
   *(During testing the codes appear in the app log instead of a real SMS/email.)*
4. On the **Verify your account** screen, enter each code. You can request a new code
   after a short wait if one doesn't arrive.
5. Once both email and phone are confirmed, your **Customer dashboard** unlocks.

Until both contacts are verified, the dashboard stays locked and you're returned to
the verification screen.

---

### Journey 2 — Customer setup (after sign-up)

Once verified, a customer can prepare for smooth checkout:

- **Delivery addresses** — save up to **3** addresses and mark **one as the default**.
  Adding, editing or choosing a different default is instant.
- **Payment methods** — save preferred payment options and set a default.

These are optional and can be added any time before placing an order.

---

### Journey 3 — Become an Operator (seller)

1. From **Become an Operator**, complete the onboarding wizard:
   - **Business info** — business name, category, location, description, and your
     login details (email, phone, password).
   - **Ghana Card** — upload the front and back of your national ID.
   - **Verification video** — upload a short video confirming your identity.
2. Your account is created with a status of **Pending review**, and you're signed in.
3. Verify your **email** and **phone** with the codes sent to you (same as customers).
4. You land on a **"Pending approval"** status page. The seller dashboard stays locked
   while a LYVO admin reviews your application.
5. When the admin **approves** you, your **Operator dashboard** unlocks. If you're
   **rejected**, the status page explains the outcome.

---

### Journey 4 — Admin reviews an Operator

1. An admin opens the **Operator verification** area, listing applications awaiting review.
2. Opening an application shows the business details, the Ghana Card images and the
   verification video.
3. The admin can mark it **In review**, then **Approve** or **Reject** it.
4. Every decision is recorded with who made it, the status change and any notes, so
   there's always a clear history.
5. On approval the operator gains access to their dashboard; on rejection they see the
   outcome on their status page.

---

### Journey 5 — Browsing as a Guest

A visitor can explore LYVO without an account: browse the **verified operator
directory** and operator profiles, and walk through how **escrow** protects a purchase.
To buy, save addresses or sell, they're invited to create an account.

---

### Journey 6 — Call-in / unregistered customers

Not everyone shops online. A customer can simply **call in**, and a LYVO customer rep
can place an order on their behalf by capturing their details — no prior account
needed. (Order placement itself arrives with the orders feature; the groundwork is in
place.)

---

### Signing in

Returning users sign in from **Login** with their email (or phone) and password and are
taken straight to the right place for their role — customer, operator or admin. If their
email and phone aren't both verified yet, they're guided back to verification first.

### Keeping accounts secure

- Verification codes expire after a few minutes and can only be tried a limited number
  of times.
- There's a short wait between code resends to prevent spam.
- Passwords are always stored encrypted, and accounts can be suspended by an admin.
