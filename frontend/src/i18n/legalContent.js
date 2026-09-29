// Terms and Conditions and Privacy Policy, kept as whole documents per language rather than
// as consumerMessages keys, since each paragraph is too long to work as a stable lookup key.
// The Filipino copy is everyday Taglish, keeping the technical and legal terms in English.
// Blocks: { heading }, { p }, { ul: [...] } or { ol: [...] }; a list item is a string or { label, text }.

export const terms = {
  en: {
    title: 'Terms and Conditions',
    updated: 'Last Updated: September 2026',
    blocks: [
      { p: 'By accessing or using the Tindahan platform, you agree to be bound by these Terms and Conditions. If you do not agree with any part of these terms, you must not use our services.' },

      { heading: '1. Account Registration and Verification' },
      {
        ol: [
          'You must provide accurate, current, and complete information during registration.',
          'You are required to verify your account using a valid 11-digit Philippine mobile number via One-Time Password (OTP).',
          'You are responsible for safeguarding your password and for all activities that occur under your account.'
        ]
      },

      { heading: '2. Location Services and GPS' },
      { p: 'Certain Tindahan features require or may request access to your device\'s location. When you grant permission, the application may use GPS or device location services to obtain your precise geographic coordinates. This location information is used to support location-based marketplace features, such as identifying nearby stores and determining proximity or distance. The availability and accuracy of these features may depend on your device\'s GPS capabilities, browser, operating system, network, and permission settings. You are responsible for allowing or denying location permissions through your device or browser settings.' },

      { heading: '3. Vendor Obligations' },
      {
        ul: [
          { label: 'Approvals:', text: 'All vendor applications are subject to review. Administrators reserve the right to approve, reject, or request revisions on store applications.' },
          { label: 'Accuracy:', text: 'Vendors must provide accurate store photos, locations, and operating hours.' },
          { label: 'Compliance:', text: 'Vendors must not list illegal, prohibited, or fraudulent items on the platform.' }
        ]
      },

      { heading: '4. Platform Rights & Account Deactivation' },
      { p: 'Tindahan administrators reserve the right to suspend, reject, or deactivate user and vendor accounts that violate these terms. Deactivated accounts are safely archived (soft-deleted) for auditing and security purposes and lose all access to public platform features.' },

      { heading: '5. Limitation of Liability' },
      { p: 'Tindahan serves as a marketplace platform connecting consumers and local sari-sari store vendors. We are not responsible for the quality, safety, or legality of the goods offered by vendors, nor the completion of transactions between users.' },

      { heading: '6. Modifications to Terms' },
      { p: 'We reserve the right to modify these terms at any time. Continued use of the application after changes implies your acceptance of the updated terms.' }
    ]
  },
  fil: {
    title: 'Terms and Conditions',
    updated: 'Huling update: September 2026',
    blocks: [
      { p: 'Sa pag-access o paggamit ng Tindahan platform, pumapayag ka na sundin ang Terms and Conditions na ito. Kung hindi ka sang-ayon sa kahit anong bahagi nito, huwag mo nang gamitin ang aming services.' },

      { heading: '1. Account Registration at Verification' },
      {
        ol: [
          'Magbigay ng tama, updated, at kumpletong information sa pag-register.',
          'Kailangan mong i-verify ang account mo gamit ang valid na 11-digit Philippine mobile number sa pamamagitan ng One-Time Password (OTP).',
          'Ikaw ang responsable sa pag-iingat ng password mo at sa lahat ng activity sa account mo.'
        ]
      },

      { heading: '2. Location Services at GPS' },
      { p: 'May ilang features ang Tindahan na nangangailangan o puwedeng humingi ng access sa location ng device mo. Kapag nag-allow ka, puwedeng gamitin ng app ang GPS o location services ng device mo para makuha ang eksaktong coordinates mo. Ginagamit ang location na ito para sa mga location-based feature ng marketplace, gaya ng paghanap ng mga kalapit na store at pag-compute ng layo o distance. Nakadepende ang availability at accuracy ng mga feature na ito sa GPS ng device mo, sa browser, operating system, network, at permission settings. Ikaw ang bahala kung ia-allow o ide-deny mo ang location permission sa settings ng device o browser mo.' },

      { heading: '3. Mga Obligasyon ng Vendor' },
      {
        ul: [
          { label: 'Approvals:', text: 'Lahat ng vendor application ay dadaan sa review. May karapatan ang mga administrator na i-approve, i-reject, o humingi ng revisions sa mga store application.' },
          { label: 'Accuracy:', text: 'Dapat tama ang store photos, location, at operating hours na ibibigay ng vendor.' },
          { label: 'Compliance:', text: 'Bawal mag-list ng illegal, prohibited, o fraudulent na items sa platform.' }
        ]
      },

      { heading: '4. Platform Rights at Account Deactivation' },
      { p: 'May karapatan ang mga Tindahan administrator na i-suspend, i-reject, o i-deactivate ang mga user at vendor account na lumalabag sa terms na ito. Ang mga deactivated account ay ligtas na ina-archive (soft-deleted) para sa auditing at security, at wala na itong access sa mga public feature ng platform.' },

      { heading: '5. Limitation of Liability' },
      { p: 'Ang Tindahan ay isang marketplace platform na nagkokonekta sa mga consumer at local sari-sari store vendor. Hindi kami responsable sa quality, safety, o legality ng mga produktong ino-offer ng vendors, pati na rin sa pagkumpleto ng transactions sa pagitan ng mga user.' },

      { heading: '6. Pagbabago sa Terms' },
      { p: 'Puwede naming baguhin ang terms na ito anumang oras. Kapag patuloy mong ginamit ang app pagkatapos ng mga pagbabago, ibig sabihin ay tinatanggap mo na ang updated na terms.' }
    ]
  }
}

export const privacy = {
  en: {
    title: 'Privacy Policy',
    updated: 'Last Updated: September 2026',
    blocks: [
      { p: 'Welcome to Tindahan. We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our mobile or web application.' },

      { heading: '1. Information We Collect' },
      {
        ul: [
          { label: 'Personal Data:', text: 'We collect your full name, email address, and mobile phone number during registration.' },
          { label: 'Media & Files:', text: 'We collect optional profile pictures for consumers, and mandatory store appearance photos for vendors.' },
          { label: 'Business Information (Vendors):', text: 'Store name, location/map coordinates, and operating schedules.' }
        ]
      },

      { heading: '2. Location Data and GPS' },
      { p: 'Tindahan may request permission to access your device\'s location. When permission is granted, our system may obtain your precise geographic location (including latitude and longitude coordinates) through your device\'s GPS or location services. This location data is used exclusively to support location-based features, such as identifying nearby stores and calculating proximity or distance. Location access is entirely permission-based, and you can control or revoke these permissions at any time through your device or browser settings.' },

      { heading: '3. How We Use Your Information' },
      {
        ul: [
          'To create, verify (via OTP), and secure your account.',
          'To process vendor applications and display approved stores to consumers.',
          'To provide customer support and respond to your inquiries.'
        ]
      },

      { heading: '4. Data Retention and Deletion' },
      { p: 'We retain personal information only for as long as necessary to fulfill the purposes outlined in this policy. When an account is deactivated or deleted by an Administrator, the account undergoes a "soft delete." This means your active profile is hidden from the public platform but securely archived in our database to preserve transaction histories and ensure platform integrity.' },

      { heading: '5. Your Rights (Data Privacy Act of 2012)' },
      { p: 'In accordance with the Philippine Data Privacy Act of 2012 (RA 10173), you have the right to be informed, object to processing, access, rectify, or request the erasure of your personal data, subject to platform limitations and legal obligations.' },

      { heading: '6. Contact Us' },
      { p: 'If you have questions or comments about this Privacy Policy, please contact our support team.' }
    ]
  },
  fil: {
    title: 'Privacy Policy',
    updated: 'Huling update: September 2026',
    blocks: [
      { p: 'Welcome sa Tindahan! Mahalaga sa amin ang pagprotekta sa personal information mo at sa karapatan mo sa privacy. Ipinapaliwanag ng Privacy Policy na ito kung paano namin kinokolekta, ginagamit, ibinabahagi, at pinoprotektahan ang information mo kapag ginagamit mo ang aming mobile o web app.' },

      { heading: '1. Information na Kinokolekta Namin' },
      {
        ul: [
          { label: 'Personal Data:', text: 'Kinokolekta namin ang full name, email address, at mobile number mo sa pag-register.' },
          { label: 'Media & Files:', text: 'Kinokolekta namin ang optional na profile picture ng mga consumer, at ang required na store photos ng mga vendor.' },
          { label: 'Business Information (Vendors):', text: 'Store name, location/map coordinates, at operating schedule.' }
        ]
      },

      { heading: '2. Location Data at GPS' },
      { p: 'Puwedeng humingi ng permission ang Tindahan para ma-access ang location ng device mo. Kapag nag-allow ka, puwedeng makuha ng system namin ang eksaktong location mo (kasama ang latitude at longitude coordinates) gamit ang GPS o location services ng device mo. Ginagamit lang ang location data na ito para sa mga location-based feature, gaya ng paghanap ng mga kalapit na store at pag-compute ng layo o distance. Permission-based ang location access, at puwede mo itong i-manage o i-revoke anumang oras sa settings ng device o browser mo.' },

      { heading: '3. Paano Namin Ginagamit ang Information Mo' },
      {
        ul: [
          'Para gumawa, i-verify (gamit ang OTP), at i-secure ang account mo.',
          'Para i-process ang vendor applications at ipakita ang mga approved store sa mga consumer.',
          'Para magbigay ng customer support at sagutin ang mga tanong mo.'
        ]
      },

      { heading: '4. Data Retention at Deletion' },
      { p: 'Itinatago lang namin ang personal information hangga\'t kailangan para sa mga layuning nakasaad sa policy na ito. Kapag na-deactivate o na-delete ng Administrator ang isang account, dadaan ito sa "soft delete." Ibig sabihin, hindi na makikita sa public platform ang active profile mo, pero ligtas itong naka-archive sa database namin para mapanatili ang transaction history at integrity ng platform.' },

      { heading: '5. Mga Karapatan Mo (Data Privacy Act of 2012)' },
      { p: 'Ayon sa Philippine Data Privacy Act of 2012 (RA 10173), may karapatan kang ma-inform, tumutol sa processing, i-access, itama, o i-request na burahin ang personal data mo, depende sa limitasyon ng platform at sa mga legal na obligasyon.' },

      { heading: '6. Contact Us' },
      { p: 'Kung may tanong o comment ka tungkol sa Privacy Policy na ito, i-contact lang ang aming support team.' }
    ]
  }
}
