# -*- coding: utf-8 -*-
"""
Validate FAQ and HowTo schema content against Anti-AI Slop and Google Rich Results rules.
"""

import re
import json

SLOP_WORDS = [
    'tapestry', 'unveil', 'unveiling', 'breathtaking', 'vibrant', 'nestled',
    'delve', 'testament', 'beacon', 'kaleidoscope', 'bustling', 'plethora',
    'myriad', 'embark', 'enchanting', 'picturesque', 'gem', 'gems', 'haven'
]

def check_slop(text):
    return [w for w in SLOP_WORDS if re.search(r'\b' + w + r'\b', text, re.I)]

FAQS = {
    "vietnam-evisa": [
        ("How much does a Vietnam e-visa cost?", "A single-entry Vietnam e-visa costs 25 USD, and a multiple-entry e-visa costs 50 USD. Fees are paid online by credit card and are non-refundable regardless of the application outcome."),
        ("What is the official Vietnam e-visa website?", "The only official government portal for Vietnam e-visas is evisa.xuatnhapcanh.gov.vn (operated by the Vietnam Immigration Department). Avoid commercial third-party websites charging excessive processing markups."),
        ("How far in advance should I apply for a Vietnam e-visa?", "Apply at least 2 weeks before your planned flight. Standard processing takes 3 to 5 business days, but processing stops during Vietnamese national holidays and technical maintenance windows."),
        ("Can I change my port of entry after my Vietnam e-visa is approved?", "No. Your entry checkpoint must match the airport, land border, or seaport approved on your official e-visa document. Changing entry ports requires submitting a new visa application."),
        ("Do I need to print a paper copy of my Vietnam e-visa?", "Yes. Airlines require a physical paper copy at departure check-in, and immigration officers stamp the paper letter upon arrival at the border checkpoint.")
    ],
    "sim-esim-vietnam": [
        ("Is eSIM or physical SIM better for traveling in Vietnam?", "An eSIM is convenient and activates before arrival if your phone is carrier-unlocked. A physical SIM card is preferable if your phone is locked or if you need a reliable local phone number to receive Grab driver calls."),
        ("Which mobile network has the best coverage in Vietnam?", "Viettel provides the widest and most reliable 4G network coverage across Vietnam, especially in mountainous regions like Sapa and Ha Giang and offshore islands. Vinaphone is a solid secondary choice in major cities."),
        ("Can I purchase a SIM card upon arrival at Vietnam airports?", "Yes. Official carrier kiosks (Viettel, Vinaphone, Mobifone) operate directly outside the baggage claim halls at Hanoi (Noi Bai), Ho Chi Minh City (Tan Son Nhat), and Da Nang international airports.")
    ],
    "vietnam-airport-arrival-checklist": [
        ("How much cash should I withdraw upon arrival at a Vietnam airport?", "Withdraw 1 to 2 million VND (approximately 40 to 80 USD) from official bank ATMs (such as Vietcombank or BIDV) in the arrivals hall to cover initial taxi fares and street expenses."),
        ("How do I avoid taxi scams at Vietnam airports?", "Use the Grab app over airport Wi-Fi or visit official prepaid taxi counters (Mai Linh or Vinasun) inside the terminal. Never follow unbadged touts offering private transportation in the terminal corridors."),
        ("How long does immigration clearance take at Hanoi and Saigon airports?", "Standard passport control takes 30 to 60 minutes. During peak arrival periods in late afternoon and evening, wait times can reach 90 minutes.")
    ],
    "hanoi-to-sapa-transport": [
        ("What is the fastest way to get from Hanoi to Sapa?", "A sleeper cabin bus or luxury limousine van traveling via the Noi Bai - Lao Cai expressway takes 5.5 to 6 hours door-to-door, which is faster and more direct than the train."),
        ("Is the overnight train from Hanoi to Sapa comfortable?", "The overnight sleeper train offers a smooth ride with air-conditioned 4-berth cabins. It arrives in Lao Cai station at 5:30 AM, where you transfer to a 50-minute mountain shuttle van up to Sapa town."),
        ("Can I book a private car transfer from Hanoi to Sapa?", "Yes. Private car transfers take approximately 5 hours door-to-door, offering total schedule flexibility and luggage convenience for families and small travel groups.")
    ],
    "ha-giang-easy-rider-vs-self-drive": [
        ("Can tourists legally ride a motorbike on the Ha Giang Loop?", "Vietnam only recognizes the 1968 International Driving Permit (IDP) with an active motorcycle endorsement. Driving on a 1949 IDP or home automobile license is illegal and voids travel medical insurance policies."),
        ("What is an Easy Rider tour in Ha Giang?", "An Easy Rider is a professional local rider who navigates the motorcycle while you sit comfortably on the rear passenger pillion seat, allowing you to view mountain landscapes without driving hazards."),
        ("How dangerous is driving the Ha Giang Loop independently?", "Independent driving carries real risks from steep mountain gradients, sharp hairpin turns on Ma Pi Leng Pass, loose gravel, unpredictable construction vehicles, and sudden mountain fog.")
    ],
    "ha-long-bay-cruise-questions-before-booking": [
        ("Should I choose a 2-day or 3-day Ha Long Bay cruise?", "A 2-day/1-night cruise gives 24 hours on the water and suits compact itineraries. A 3-day/2-night cruise travels deeper into quieter Lan Ha or Bai Tu Long bays with unhurried kayaking and swimming time."),
        ("What amenities are included in an overnight Ha Long Bay cruise?", "Cruise rates include all onboard meals, private en-suite cabin, cave excursions, and kayak access. Highway transfers between Hanoi and the harbor and personal beverages are usually billed separately."),
        ("What is the cruise cancellation policy during bad weather?", "The local maritime port authority suspends sailings during typhoons or dense fog for passenger safety. Reputable operators provide full refunds or day-tour alternatives in accordance with maritime law.")
    ],
    "trang-an-vs-tam-coc": [
        ("Is Trang An or Tam Coc better in Ninh Binh?", "Trang An features dramatic karst water cave tunnels, strict lifejacket rules, and organized UNESCO management. Tam Coc offers open river paddling through scenic rice fields with rowers using their feet."),
        ("How long does the boat tour take in Trang An and Tam Coc?", "Trang An boat circuits last 2.5 to 3 hours through 3 to 4 caves. Tam Coc boat trips take approximately 1.5 to 2 hours along the Ngo Dong river."),
        ("Which boat route in Trang An is recommended?", "Route 2 (visiting Dot Cave and Dia Linh Cave) and Route 3 (featuring the 1,000-meter cave) provide the finest combination of karst scenery, cave passages, and historic temples.")
    ],
    "vietnam-travel-cost": [
        ("What is a realistic daily travel budget for Vietnam?", "Budget backpackers spend 30 to 45 USD per day. Mid-range travelers staying in 3-star boutique hotels and taking domestic flights spend 70 to 120 USD per day. Luxury travelers spend 200 USD and up per day."),
        ("Is Vietnam cheaper than Thailand for travelers?", "Vietnam is generally 15 to 25 percent less expensive than Thailand for street meals, local transportation, and city boutique accommodations, while guided expeditions and luxury cruises cost similar amounts."),
        ("Do I need cash in Vietnam or are cards widely accepted?", "Credit cards are accepted at mid-range hotels, supermarkets, and established restaurants in major cities. Cash in Vietnamese Dong (VND) is essential for street dining, small market stalls, and rural taxis.")
    ]
}

HOWTOS = {
    "vietnam-evisa": {
        "name": "How to Apply for a Vietnam E-Visa Online",
        "description": "Step-by-step instructions for submitting a valid Vietnam electronic visa application through the official government immigration portal.",
        "steps": [
            ("Prepare Required Documents", "Ensure your passport has at least 6 months validity. Prepare a sharp JPEG digital scan of your passport bio page and a passport-style portrait photo on a plain white background without glasses."),
            ("Access the Official Immigration Portal", "Visit the official government website at evisa.xuatnhapcanh.gov.vn. Avoid third-party commercial agency portals that charge marked-up intermediary fees."),
            ("Complete the Application Form", "Fill in your full legal name, date of birth, passport details, temporary address in Vietnam, and select your specific entry and exit international border checkpoints."),
            ("Pay the Visa Processing Fee", "Pay the official non-refundable fee (25 USD for single-entry up to 90 days, or 50 USD for multiple-entry) using an international debit or credit card."),
            ("Track Status and Print Approval Letter", "Record your registration code. Check application status after 3 to 5 working days. Once approved, download and print two physical copies of the e-visa letter for departure check-in and border inspection.")
        ]
    },
    "sim-esim-vietnam": {
        "name": "How to Buy and Set Up an eSIM for Vietnam",
        "description": "Step-by-step guide to purchasing, installing, and activating an electronic SIM profile for mobile data in Vietnam.",
        "steps": [
            ("Verify Phone Compatibility", "Confirm your smartphone is carrier-unlocked and supports eSIM functionality in device cellular settings."),
            ("Select Network and Data Plan", "Choose an authorized provider powered by the Viettel network for superior nationwide coverage, especially in highland and island destinations."),
            ("Install eSIM via QR Code", "Receive your digital activation QR code by email. Open device settings, select Add eSIM, and scan the QR code before boarding your flight."),
            ("Activate Data Roaming on Arrival", "Upon touchdown at any Vietnam international airport, switch your cellular data line to the Vietnam eSIM profile and toggle Data Roaming to ON.")
        ]
    }
}

def main():
    print("=== Checking FAQ Content for AI Slop & Length ===")
    total_q = 0
    for slug, qas in FAQS.items():
        print(f"\n[{slug}] ({len(qas)} Q&As):")
        for q, a in qas:
            total_q += 1
            slop_q = check_slop(q)
            slop_a = check_slop(a)
            assert not slop_q, f"Slop in question: {slop_q}"
            assert not slop_a, f"Slop in answer: {slop_a}"
            assert len(q) >= 15, f"Question too short: {q}"
            assert len(a) >= 50, f"Answer too short: {a}"
            print(f"  Q: {q}")
            print(f"     A: {a[:80]}... ({len(a)} chars)")

    print(f"\nTotal FAQ Questions validated: {total_q}")

    print("\n=== Checking HowTo Content for AI Slop ===")
    for slug, ht in HOWTOS.items():
        print(f"\n[{slug}] HowTo: {ht['name']}")
        assert not check_slop(ht['name'])
        assert not check_slop(ht['description'])
        for sname, stext in ht['steps']:
            assert not check_slop(sname)
            assert not check_slop(stext)
            print(f"  Step: {sname} -> {stext[:70]}...")

    print("\n[SUCCESS] All FAQ and HowTo schema data passed validation with 0 AI slop.")

if __name__ == '__main__':
    main()
