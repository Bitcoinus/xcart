X-Cart vartotojo vadovas Bitcoinus priedo integracijai

KONFIGŪRACIJOS PRADŽIA

Įdiekite Bitcoinus priedą.

MOKĖJIMO MODULIO ĮGALINIMAS

1. Pasirinkite „Store Setup“ > „Payment Methods“;
2. Paspauskite „Add Payment Method“;
3. Pasirinkite „Bitcoinus“ ir spauskite „Install“;
4. Nustatymų puslapyje („Settings“) užpildykite tuščius laukus. Projekto ID ir slaptą raktą galite rasti jūsų „Bitcoinus“ paskyroje („Project Settings“);
5. Paspauskite „Save Changes“;
6. Eikite į „Payment Methods“ skiltį;
7. Aktyvuokite Bitcoinus.

Callback URL nustatymas:

1. Nukopijuokite URL į projekto nustatymus (Settings) jūsų Bitcoinus paskyroje pasirinkdami „Callback URL“:
https://example.com/xcart/cart.php?target=payment_return&txn_id_name=transactionID&transactionID=[ORDERID]
2. Pakeiskite „example.com“ į Jūsų e-parduotuvės URL;
3. Paspauskite „Save Changes“.
