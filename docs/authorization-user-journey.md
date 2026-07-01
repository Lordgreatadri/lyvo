# LYVO — Authorization User Journey

> Plain-language walkthrough of who can do what on LYVO, and how admins manage people
> and permissions. Technical details live in
> [DevDocs/authorization.md](../DevDocs/authorization.md). For sign-up and
> verification see [authentication-user-journey.md](authentication-user-journey.md).

---

## What "authorization" means here

Authentication is *proving who you are*. **Authorization** is *what you're allowed to
do* once you're in. On LYVO your **account type** decides your starting permissions,
and an admin can fine-tune things from there.

### Who can do what

| Role | Can do | Cannot do |
|------|--------|-----------|
| **Guest** | Browse public pages, view operators, follow the escrow walk-through | Buy, sell, or manage anything |
| **Customer** | Manage their addresses & payment methods, transact through escrow, leave reviews, raise disputes | See other people's accounts, approve operators |
| **Operator** | Everything a customer needs plus manage their own products and view their verification status | Approve other operators, manage users |
| **Admin** | Everything — manage users, approve operators, control roles & permissions | — |

---

### Journey 1 — Everyone manages their own profile

From any dashboard, open **Settings** (in the sidebar). There you can:

1. Update your **name**, **email** and **phone number**.
2. If you change your email or phone, LYVO sends a fresh verification code to the new
   contact and asks you to confirm it before it's trusted again.
3. Change your **password**.
4. Delete your account permanently (with password confirmation).

---

### Journey 2 — Admin reviews and approves people

1. In the admin sidebar, open **Users**.
2. See everyone at a glance, with quick counters for total users, operators, customers,
   frozen accounts and **pending review**.
3. Search by name, email or phone, or filter by account type, status, or
   "pending operators".
4. Open a user to see their full profile. For an operator awaiting review you can open
   the full verification screen (ID + video) or **approve** them right there.

---

### Journey 3 — Admin freezes or reactivates an account

On a user's page an admin can **freeze** an account (the person can no longer sign in)
and later **reactivate** it. Admins can't freeze their own account, so they never lock
themselves out.

---

### Journey 4 — Admin assigns roles to a user

On a user's page, tick the **roles** that person should carry and save. Roles bundle
permissions together, so assigning a role instantly grants everything that role can do.
Admins can't change the roles on their own account.

---

### Journey 5 — Admin tunes what a role can do

1. In the admin sidebar, open **Roles**.
2. Each role (admin, customer, operator) lists its permissions grouped by area
   (users, escrow, disputes, products, reviews, and so on).
3. Tick or untick permissions and save. The change applies to everyone with that role.

> Admins always keep full access, so tightening a role never accidentally locks the
> platform's owners out.
