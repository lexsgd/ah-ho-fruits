<?php
/**
 * Plugin Name: Ah Ho Legal Pages Setup
 * Description: One-time setup script to create Terms & Conditions and Privacy Policy pages
 * Version: 1.0.0
 * Author: Ah Ho Fruit Trading Co
 *
 * INSTRUCTIONS:
 * 1. Upload this file to wp-content/plugins/
 * 2. Activate the plugin in WordPress admin
 * 3. Pages will be auto-created
 * 4. Deactivate and delete this plugin after use
 */

defined( 'ABSPATH' ) || exit;

register_activation_hook( __FILE__, 'ah_ho_create_legal_pages' );

function ah_ho_create_legal_pages() {

    // Terms and Conditions content
    $terms_content = <<<'HTML'
<p><strong>Last Updated:</strong> 26 January 2026</p>

<p>Welcome to Ah Ho Fruit Trading Co ("we," "our," or "us"). These Terms and Conditions govern your use of our website at <strong>ahhofruit.com</strong> (currently accessible at fruits.heymag.app) and your purchase of products from us.</p>

<p>By accessing our website and placing an order, you agree to be bound by these Terms and Conditions. If you do not agree with any part of these terms, please do not use our website or services.</p>

<hr>

<h2>1. Company Information</h2>

<p><strong>Company Name:</strong> AH HO FRUIT TRADING CO<br>
<strong>Registered Address:</strong> 230A Pandan Loop #03-14, Singapore<br>
<strong>Contact Phone:</strong> +65 80138128<br>
<strong>Contact Email:</strong> ahhofruit@singnet.com.sg<br>
<strong>Website:</strong> ahhofruit.com (currently fruits.heymag.app)</p>

<hr>

<h2>2. General Terms</h2>

<h3>2.1 Acceptance of Terms</h3>
<p>By placing an order through our website, you confirm that:</p>
<ul>
    <li>You are at least 18 years old or have parental/guardian consent</li>
    <li>You are legally capable of entering into binding contracts</li>
    <li>You will use our services in compliance with these Terms and all applicable laws</li>
    <li>All information you provide is accurate, current, and complete</li>
</ul>

<h3>2.2 Changes to Terms</h3>
<p>We reserve the right to modify these Terms and Conditions at any time. Changes will be effective immediately upon posting to our website. Your continued use of our services after changes are posted constitutes acceptance of the modified terms. We will indicate the "Last Updated" date at the top of this page.</p>

<h3>2.3 Use of Website</h3>
<p>You agree to use our website only for lawful purposes and in a way that does not infringe the rights of, restrict, or inhibit anyone else's use and enjoyment of the website. Prohibited behavior includes harassing or causing distress or inconvenience to any other user, transmitting obscene or offensive content, or disrupting the normal flow of dialogue within our website.</p>

<hr>

<h2>3. Products and Services</h2>

<h3>3.1 Product Descriptions</h3>
<p>We strive to ensure that product descriptions, images, and prices on our website are accurate. However, we do not warrant that product descriptions or other content is accurate, complete, reliable, current, or error-free. Fresh produce may vary in size, color, and appearance from images shown.</p>

<h3>3.2 Product Quality</h3>
<p>All fresh fruits and produce are carefully selected and quality-checked before delivery. We guarantee freshness at the time of delivery. However, as these are perishable items, proper storage after delivery is your responsibility.</p>

<h3>3.3 Seasonal Availability</h3>
<p>Some products may be subject to seasonal availability. We reserve the right to substitute similar products of equal or greater value if your selected items become unavailable. For Omakase Fruit Boxes, we will select the best available seasonal fruits.</p>

<h3>3.4 Special Requests</h3>
<p>While we accommodate special requests (fruit preferences, allergies) wherever possible, we cannot guarantee specific fruits will always be available. Special requests are noted but not contractually binding.</p>

<hr>

<h2>4. Pricing and Payment</h2>

<h3>4.1 Prices</h3>
<p>All prices are in Singapore Dollars (SGD) and include GST where applicable. Prices are subject to change without notice. The price charged will be the price displayed at the time you place your order.</p>

<h3>4.2 Payment Methods</h3>
<p>We accept the following payment methods:</p>
<ul>
    <li>Credit/Debit Cards (Visa, Mastercard, American Express)</li>
    <li>PayPal</li>
    <li>Cash on Delivery (COD) - subject to availability and delivery area</li>
</ul>

<h3>4.3 Cash on Delivery Terms</h3>
<p>For COD orders:</p>
<ul>
    <li>Exact cash payment preferred; change will be provided if necessary</li>
    <li>Payment must be made at the time of delivery</li>
    <li>Refusal to pay may result in restriction of future COD privileges</li>
</ul>

<h3>4.4 Payment Authorization</h3>
<p>By providing payment information, you authorize us to charge the total amount to your selected payment method. You represent and warrant that you have the legal right to use any payment method you provide.</p>

<hr>

<h2>5. Orders and Confirmations</h2>

<h3>5.1 Order Acceptance</h3>
<p>Your order constitutes an offer to purchase products. We reserve the right to accept or decline any order for any reason. We may require additional verification or information before accepting any order.</p>

<h3>5.2 Order Confirmation</h3>
<p>You will receive an email confirmation once your order is placed. This confirmation does not constitute acceptance of your order. Acceptance occurs when we dispatch your order and send a dispatch confirmation email.</p>

<h3>5.3 Order Cancellation by Customer</h3>
<p>You may cancel your order within 2 hours of placing it by contacting us at +65 80138128 or ahhofruit@singnet.com.sg. After this time, cancellation may not be possible if the order is already being prepared or dispatched.</p>

<h3>5.4 Order Cancellation by Us</h3>
<p>We reserve the right to cancel any order due to:</p>
<ul>
    <li>Product unavailability</li>
    <li>Pricing or product description errors</li>
    <li>Payment authorization failure</li>
    <li>Suspected fraudulent activity</li>
</ul>

<hr>

<h2>6. Delivery</h2>

<h3>6.1 Delivery Area</h3>
<p>We currently deliver within Singapore. Delivery charges may vary based on location and order value. Minimum order requirements may apply for certain areas.</p>

<h3>6.2 Delivery Times</h3>
<p>We aim to deliver within the time slot selected at checkout. Delivery times are estimates and not guaranteed. We will make reasonable efforts to deliver within the specified time frame.</p>

<h3>6.3 Delivery Charges</h3>
<p>Delivery charges are calculated based on your location and order value. These charges will be clearly displayed before you complete your order. Free delivery may be offered for orders above a certain value.</p>

<h3>6.4 Failed Deliveries</h3>
<p>If delivery cannot be completed due to:</p>
<ul>
    <li>Incorrect or incomplete address</li>
    <li>No one available to receive the order</li>
    <li>Refusal to accept delivery</li>
</ul>
<p>We may charge a re-delivery fee or cancel the order. Perishable items cannot be re-delivered and no refund will be provided for customer-caused failed deliveries.</p>

<h3>6.5 Delivery Acceptance</h3>
<p>Upon delivery, please inspect your order immediately. By accepting delivery, you acknowledge receipt of the products. Any issues with product quality must be reported within 24 hours.</p>

<hr>

<h2>7. Returns and Refunds</h2>

<h3>7.1 Perishable Products Policy</h3>
<p>Due to the perishable nature of fresh fruits and produce, returns are generally not accepted. However, we stand behind the quality of our products.</p>

<h3>7.2 Quality Issues</h3>
<p>If you receive products that are:</p>
<ul>
    <li>Damaged during delivery</li>
    <li>Not fresh or of poor quality</li>
    <li>Incorrect items (wrong product delivered)</li>
</ul>
<p>Please contact us within 24 hours of delivery at +65 80138128 or ahhofruit@singnet.com.sg with photos of the issue.</p>

<h3>7.3 Refund Process</h3>
<p>If your claim is approved, we will offer:</p>
<ul>
    <li>Full or partial refund to your original payment method</li>
    <li>Store credit for future purchases</li>
    <li>Replacement delivery at no additional charge</li>
</ul>

<h3>7.4 Refund Timeline</h3>
<p>Approved refunds will be processed within 7-14 business days. The time for the refund to appear in your account depends on your payment provider.</p>

<hr>

<h2>8. Gift Messages and Special Requests</h2>

<h3>8.1 Gift Service</h3>
<p>We offer gift message services for orders. Gift messages will be printed and included with your delivery. We are not responsible for errors in gift messages as entered by you.</p>

<h3>8.2 Allergy and Preference Requests</h3>
<p>While we make every effort to accommodate special requests regarding fruit preferences and allergies, we cannot guarantee that all requests will be fulfilled due to product availability.</p>

<h3>8.3 Disclaimer</h3>
<p>You are responsible for accurately communicating any allergies or dietary restrictions. We are not liable for allergic reactions if full allergy information was not provided or if cross-contamination occurs during handling or delivery.</p>

<hr>

<h2>9. Intellectual Property</h2>

<h3>9.1 Website Content</h3>
<p>All content on our website, including but not limited to text, graphics, logos, images, and software, is the property of AH HO FRUIT TRADING CO or its licensors and is protected by Singapore and international copyright laws.</p>

<h3>9.2 Restrictions</h3>
<p>You may not:</p>
<ul>
    <li>Reproduce, duplicate, or copy material from our website for commercial purposes</li>
    <li>Redistribute content from our website without permission</li>
    <li>Use our trademarks or branding without written consent</li>
</ul>

<hr>

<h2>10. User Accounts</h2>

<h3>10.1 Account Creation</h3>
<p>To place orders, you may need to create an account. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>

<h3>10.2 Account Security</h3>
<p>You must notify us immediately of any unauthorized use of your account or any other breach of security. We are not liable for any loss or damage arising from your failure to protect your account information.</p>

<h3>10.3 Account Termination</h3>
<p>We reserve the right to suspend or terminate your account at any time for violation of these Terms or for any other reason we deem appropriate.</p>

<hr>

<h2>11. Limitation of Liability</h2>

<h3>11.1 To the Maximum Extent Permitted by Law</h3>
<p>AH HO FRUIT TRADING CO shall not be liable for:</p>
<ul>
    <li>Any indirect, incidental, special, or consequential damages</li>
    <li>Loss of profits, revenue, data, or use</li>
    <li>Damages arising from your use or inability to use our website or services</li>
</ul>

<h3>11.2 Maximum Liability</h3>
<p>Our total liability to you for any claims arising from your use of our services shall not exceed the amount you paid for the products in question.</p>

<h3>11.3 Consumer Rights</h3>
<p>Nothing in these Terms shall exclude or limit our liability for death or personal injury caused by negligence, fraud, or any other liability that cannot be excluded or limited under Singapore law.</p>

<hr>

<h2>12. Force Majeure</h2>

<p>We shall not be liable for any failure or delay in performing our obligations due to circumstances beyond our reasonable control, including but not limited to:</p>
<ul>
    <li>Acts of God, natural disasters, severe weather</li>
    <li>Pandemics, epidemics, health emergencies</li>
    <li>War, terrorism, civil unrest</li>
    <li>Government restrictions or regulations</li>
    <li>Supplier failures, transportation disruptions</li>
</ul>

<hr>

<h2>13. Privacy and Data Protection</h2>

<p>Your use of our website and services is also governed by our Privacy Policy, which complies with Singapore's Personal Data Protection Act 2012 (PDPA). Please review our Privacy Policy to understand how we collect, use, and protect your personal data.</p>

<hr>

<h2>14. Governing Law and Jurisdiction</h2>

<h3>14.1 Governing Law</h3>
<p>These Terms and Conditions are governed by and construed in accordance with the laws of Singapore.</p>

<h3>14.2 Dispute Resolution</h3>
<p>Any disputes arising from these Terms or your use of our services shall be subject to the exclusive jurisdiction of the courts of Singapore.</p>

<h3>14.3 Consumer Protection</h3>
<p>Your statutory rights under the Singapore Consumer Protection (Fair Trading) Act and other applicable consumer protection laws are not affected by these Terms.</p>

<hr>

<h2>15. Miscellaneous</h2>

<h3>15.1 Entire Agreement</h3>
<p>These Terms and Conditions, together with our Privacy Policy, constitute the entire agreement between you and AH HO FRUIT TRADING CO regarding your use of our website and services.</p>

<h3>15.2 Severability</h3>
<p>If any provision of these Terms is found to be invalid or unenforceable, the remaining provisions shall continue in full force and effect.</p>

<h3>15.3 Waiver</h3>
<p>Our failure to enforce any right or provision of these Terms shall not constitute a waiver of such right or provision.</p>

<h3>15.4 Assignment</h3>
<p>You may not assign or transfer these Terms or your rights and obligations hereunder without our prior written consent. We may assign these Terms without restriction.</p>

<hr>

<h2>16. Contact Information</h2>

<p>If you have any questions about these Terms and Conditions, please contact us:</p>

<p><strong>AH HO FRUIT TRADING CO</strong><br>
230A Pandan Loop #03-14, Singapore<br>
Phone: +65 80138128<br>
Email: ahhofruit@singnet.com.sg<br>
Website: ahhofruit.com</p>

<hr>

<p><em>By using our website and services, you acknowledge that you have read, understood, and agree to be bound by these Terms and Conditions.</em></p>
HTML;

    // Privacy Policy content
    $privacy_content = <<<'HTML'
<p><strong>Last Updated:</strong> 26 January 2026</p>

<p>AH HO FRUIT TRADING CO ("we," "our," or "us") is committed to protecting your privacy and personal data. This Privacy Policy explains how we collect, use, disclose, and safeguard your personal information when you visit our website <strong>ahhofruit.com</strong> (currently accessible at fruits.heymag.app) and use our services.</p>

<p>This policy complies with Singapore's Personal Data Protection Act 2012 (PDPA) and other applicable data protection regulations.</p>

<hr>

<h2>1. Company Information</h2>

<p><strong>Data Controller:</strong> AH HO FRUIT TRADING CO<br>
<strong>Registered Address:</strong> 230A Pandan Loop #03-14, Singapore<br>
<strong>Contact Phone:</strong> +65 80138128<br>
<strong>Contact Email:</strong> ahhofruit@singnet.com.sg<br>
<strong>Website:</strong> ahhofruit.com (currently fruits.heymag.app)</p>

<hr>

<h2>2. Personal Data We Collect</h2>

<h3>2.1 Information You Provide Directly</h3>

<p>When you use our website and services, we collect information that you voluntarily provide, including:</p>

<h4>Account Registration:</h4>
<ul>
    <li>Name (first and last name)</li>
    <li>Email address</li>
    <li>Password (encrypted)</li>
    <li>Phone number</li>
</ul>

<h4>Order and Delivery Information:</h4>
<ul>
    <li>Billing address (street address, unit number, postal code, city)</li>
    <li>Delivery address (if different from billing)</li>
    <li>Contact phone number for delivery</li>
    <li>Special delivery instructions</li>
    <li>Product preferences (e.g., "more strawberries," allergy information)</li>
    <li>Gift messages</li>
</ul>

<h4>Payment Information:</h4>
<ul>
    <li>Credit/debit card details (processed securely by payment providers)</li>
    <li>PayPal account information (handled by PayPal)</li>
    <li>Billing address</li>
</ul>

<h3>2.2 Information Collected Automatically</h3>

<p>When you visit our website, we automatically collect certain information:</p>

<h4>Usage Data:</h4>
<ul>
    <li>IP address</li>
    <li>Browser type and version</li>
    <li>Operating system</li>
    <li>Pages visited and time spent on pages</li>
    <li>Referring website</li>
    <li>Date and time of visit</li>
    <li>Device information</li>
</ul>

<h4>Cookies and Tracking Technologies:</h4>
<p>We use cookies and similar tracking technologies to enhance your experience. See Section 9 for detailed cookie information.</p>

<hr>

<h2>3. How We Use Your Personal Data</h2>

<p>We use your personal data for the following purposes:</p>

<h3>3.1 Order Fulfillment</h3>
<ul>
    <li>Process and deliver your orders</li>
    <li>Communicate order status and delivery updates</li>
    <li>Handle special requests and preferences</li>
    <li>Process payments and prevent fraud</li>
</ul>

<h3>3.2 Customer Service</h3>
<ul>
    <li>Respond to inquiries and support requests</li>
    <li>Handle returns, refunds, and complaints</li>
    <li>Provide product recommendations</li>
    <li>Improve our products and services</li>
</ul>

<h3>3.3 Marketing Communications</h3>
<ul>
    <li>Send promotional emails about new products, special offers, and seasonal fruits (with your consent)</li>
    <li>Personalize marketing based on your preferences and order history</li>
    <li>Send newsletters (you may opt out at any time)</li>
</ul>

<h3>3.4 Analytics and Improvement</h3>
<ul>
    <li>Analyze website usage to improve functionality</li>
    <li>Understand customer preferences and buying patterns</li>
    <li>Conduct market research</li>
    <li>Improve our products and services</li>
</ul>

<h3>3.5 Legal and Compliance</h3>
<ul>
    <li>Comply with legal obligations (tax, accounting, consumer protection laws)</li>
    <li>Prevent fraud and protect against security threats</li>
    <li>Enforce our Terms and Conditions</li>
    <li>Resolve disputes</li>
</ul>

<hr>

<h2>4. Legal Basis for Processing (PDPA)</h2>

<p>Under Singapore's PDPA, we process your personal data on the following legal bases:</p>

<ul>
    <li><strong>Consent:</strong> You have given clear consent for specific purposes (e.g., marketing emails)</li>
    <li><strong>Contract Performance:</strong> Processing is necessary to fulfill our contract with you (e.g., order delivery)</li>
    <li><strong>Legal Obligation:</strong> Processing is required to comply with Singapore law (e.g., tax records)</li>
    <li><strong>Legitimate Interests:</strong> Processing is necessary for our legitimate business interests (e.g., fraud prevention, analytics)</li>
</ul>

<hr>

<h2>5. Data Sharing and Disclosure</h2>

<p>We do not sell your personal data. We may share your data with the following third parties:</p>

<h3>5.1 Service Providers</h3>
<ul>
    <li><strong>Payment Processors:</strong> Stripe, PayPal (to process payments securely)</li>
    <li><strong>Delivery Partners:</strong> Third-party logistics providers (to deliver your orders)</li>
    <li><strong>Email Service Providers:</strong> For sending transactional and marketing emails</li>
    <li><strong>Analytics Providers:</strong> Google Analytics (to analyze website usage)</li>
    <li><strong>Cloud Hosting:</strong> Server hosting providers (to store website data)</li>
</ul>

<h3>5.2 Legal Requirements</h3>
<p>We may disclose your personal data if required by law, court order, or government authority, or to protect our rights, property, or safety.</p>

<h3>5.3 Business Transfers</h3>
<p>In the event of a merger, acquisition, or sale of assets, your personal data may be transferred to the new owner. We will notify you of any such change.</p>

<hr>

<h2>6. International Data Transfers</h2>

<p>Your personal data is primarily stored and processed in Singapore. If we transfer data outside Singapore, we will ensure adequate protection through:</p>
<ul>
    <li>Standard contractual clauses approved by relevant authorities</li>
    <li>Ensuring the recipient country has adequate data protection laws</li>
    <li>Obtaining your explicit consent where required</li>
</ul>

<hr>

<h2>7. Data Security</h2>

<p>We implement industry-standard security measures to protect your personal data:</p>

<ul>
    <li><strong>Encryption:</strong> SSL/TLS encryption for data transmission</li>
    <li><strong>Secure Payment Processing:</strong> PCI-DSS compliant payment providers</li>
    <li><strong>Access Controls:</strong> Limited access to personal data on a need-to-know basis</li>
    <li><strong>Password Protection:</strong> Encrypted password storage</li>
    <li><strong>Regular Security Audits:</strong> Ongoing monitoring for vulnerabilities</li>
</ul>

<p>However, no method of transmission over the internet is 100% secure. While we strive to protect your data, we cannot guarantee absolute security.</p>

<hr>

<h2>8. Data Retention</h2>

<p>We retain your personal data for as long as necessary to fulfill the purposes outlined in this policy:</p>

<ul>
    <li><strong>Account Data:</strong> Until you request deletion or close your account</li>
    <li><strong>Order History:</strong> 7 years (for tax and accounting purposes as required by Singapore law)</li>
    <li><strong>Marketing Data:</strong> Until you opt out or withdraw consent</li>
    <li><strong>Analytics Data:</strong> Anonymized and retained for up to 26 months</li>
</ul>

<p>After the retention period, we will securely delete or anonymize your personal data.</p>

<hr>

<h2>9. Your Rights Under PDPA</h2>

<p>Under Singapore's PDPA, you have the following rights:</p>

<h3>9.1 Right to Access</h3>
<p>You may request a copy of the personal data we hold about you.</p>

<h3>9.2 Right to Correction</h3>
<p>You may request correction of inaccurate or incomplete personal data.</p>

<h3>9.3 Right to Withdraw Consent</h3>
<p>You may withdraw consent for marketing communications or other optional data processing at any time.</p>

<h3>9.4 Right to Data Portability</h3>
<p>You may request your personal data in a structured, commonly used format.</p>

<h3>9.5 Right to Erasure</h3>
<p>You may request deletion of your personal data, subject to legal retention requirements.</p>

<h3>9.6 How to Exercise Your Rights</h3>
<p>To exercise any of these rights, contact us at:</p>
<ul>
    <li>Email: ahhofruit@singnet.com.sg</li>
    <li>Phone: +65 80138128</li>
</ul>
<p>We will respond to your request within 30 days.</p>

<hr>

<h2>10. Cookies and Tracking Technologies</h2>

<p>We use cookies and similar technologies to enhance your browsing experience. Cookies are small text files stored on your device.</p>

<h3>10.1 Types of Cookies We Use</h3>

<h4>Essential Cookies (Required):</h4>
<ul>
    <li>Session management (shopping cart, login)</li>
    <li>Security and authentication</li>
    <li>Load balancing</li>
</ul>

<h4>Performance Cookies (Optional):</h4>
<ul>
    <li>Google Analytics (website traffic analysis)</li>
    <li>Page load times and performance monitoring</li>
</ul>

<h4>Functionality Cookies (Optional):</h4>
<ul>
    <li>Remember your preferences (language, delivery address)</li>
    <li>Personalized user experience</li>
</ul>

<h4>Marketing Cookies (Optional - Requires Consent):</h4>
<ul>
    <li>Facebook Pixel, Google Ads (for targeted advertising)</li>
    <li>Email campaign tracking</li>
</ul>

<h3>10.2 Managing Cookies</h3>
<p>You can control cookies through your browser settings. Disabling essential cookies may affect website functionality. To opt out of Google Analytics, visit: <a href="https://tools.google.com/dlpage/gaoptout">Google Analytics Opt-out</a></p>

<hr>

<h2>11. Children's Privacy</h2>

<p>Our services are not intended for individuals under 18 years of age. We do not knowingly collect personal data from children. If you are under 18, please obtain parental consent before using our website or making purchases.</p>

<p>If we become aware that we have collected personal data from a child without parental consent, we will delete it promptly.</p>

<hr>

<h2>12. Changes to This Privacy Policy</h2>

<p>We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. We will notify you of significant changes by:</p>
<ul>
    <li>Posting the updated policy on our website with a new "Last Updated" date</li>
    <li>Sending an email notification (for material changes)</li>
</ul>

<p>Your continued use of our services after changes are posted constitutes acceptance of the updated policy.</p>

<hr>

<h2>13. Complaints and PDPC</h2>

<p>If you have concerns about how we handle your personal data, please contact us first at ahhofruit@singnet.com.sg. We will investigate and respond within 30 days.</p>

<p>If you are not satisfied with our response, you may file a complaint with Singapore's Personal Data Protection Commission (PDPC):</p>

<p><strong>Personal Data Protection Commission (PDPC)</strong><br>
Website: <a href="https://www.pdpc.gov.sg">www.pdpc.gov.sg</a><br>
Email: info@pdpc.gov.sg</p>

<hr>

<h2>14. Third-Party Links</h2>

<p>Our website may contain links to third-party websites (e.g., social media, payment providers). We are not responsible for the privacy practices of these external sites. Please review their privacy policies before providing personal data.</p>

<hr>

<h2>15. Email Communications</h2>

<h3>15.1 Transactional Emails</h3>
<p>We will send you transactional emails related to your orders (order confirmation, shipping updates). You cannot opt out of these essential communications.</p>

<h3>15.2 Marketing Emails</h3>
<p>With your consent, we will send promotional emails about new products, special offers, and seasonal fruits. You may opt out at any time by:</p>
<ul>
    <li>Clicking "Unsubscribe" in any marketing email</li>
    <li>Contacting us at ahhofruit@singnet.com.sg</li>
    <li>Updating your account preferences</li>
</ul>

<hr>

<h2>16. Contact Us</h2>

<p>If you have any questions about this Privacy Policy or how we handle your personal data, please contact us:</p>

<p><strong>AH HO FRUIT TRADING CO</strong><br>
Data Protection Officer<br>
230A Pandan Loop #03-14, Singapore<br>
Phone: +65 80138128<br>
Email: ahhofruit@singnet.com.sg<br>
Website: ahhofruit.com</p>

<hr>

<p><em>By using our website and services, you acknowledge that you have read and understood this Privacy Policy and consent to the collection, use, and disclosure of your personal data as described herein.</em></p>
HTML;

    // Create Terms and Conditions page
    $terms_page_id = wp_insert_post( array(
        'post_title'   => 'Terms and Conditions',
        'post_content' => $terms_content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_name'    => 'terms-and-conditions',
    ) );

    // Create Privacy Policy page
    $privacy_page_id = wp_insert_post( array(
        'post_title'   => 'Privacy Policy',
        'post_content' => $privacy_content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_name'    => 'privacy-policy',
    ) );

    // Set Privacy Policy page in WordPress settings
    if ( $privacy_page_id ) {
        update_option( 'wp_page_for_privacy_policy', $privacy_page_id );
    }

    // Set Terms page in WooCommerce settings
    if ( $terms_page_id && class_exists( 'WooCommerce' ) ) {
        update_option( 'woocommerce_terms_page_id', $terms_page_id );
    }

    // Add to footer menu
    $footer_menu_updated = ah_ho_add_to_footer_menu( $terms_page_id, $privacy_page_id );

    // Add admin notice
    add_option( 'ah_ho_legal_pages_created', array(
        'terms_page_id'      => $terms_page_id,
        'privacy_page_id'    => $privacy_page_id,
        'footer_menu_updated' => $footer_menu_updated,
        'created_at'         => current_time( 'mysql' ),
    ) );
}

/**
 * Add legal pages to footer menu
 */
function ah_ho_add_to_footer_menu( $terms_page_id, $privacy_page_id ) {
    // Get all registered menus
    $locations = get_theme_mod( 'nav_menu_locations' );

    // Find footer menu (common footer menu locations in Avada theme)
    $footer_menu_id = null;
    $footer_locations = array( 'footer_menu', 'footer', 'secondary', 'footer-menu' );

    foreach ( $footer_locations as $location ) {
        if ( isset( $locations[ $location ] ) ) {
            $footer_menu_id = $locations[ $location ];
            break;
        }
    }

    // If no footer menu found, try to find menu by name
    if ( ! $footer_menu_id ) {
        $menus = wp_get_nav_menus();
        foreach ( $menus as $menu ) {
            if ( stripos( $menu->name, 'footer' ) !== false ) {
                $footer_menu_id = $menu->term_id;
                break;
            }
        }
    }

    // If still no footer menu, create one
    if ( ! $footer_menu_id ) {
        $menu_id = wp_create_nav_menu( 'Footer Menu' );
        if ( ! is_wp_error( $menu_id ) ) {
            $footer_menu_id = $menu_id;

            // Try to assign to footer location
            if ( isset( $footer_locations[0] ) ) {
                $locations[ $footer_locations[0] ] = $footer_menu_id;
                set_theme_mod( 'nav_menu_locations', $locations );
            }
        }
    }

    if ( $footer_menu_id ) {
        // Add Terms and Conditions to footer menu
        wp_update_nav_menu_item( $footer_menu_id, 0, array(
            'menu-item-title'     => 'Terms and Conditions',
            'menu-item-object'    => 'page',
            'menu-item-object-id' => $terms_page_id,
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
        ) );

        // Add Privacy Policy to footer menu
        wp_update_nav_menu_item( $footer_menu_id, 0, array(
            'menu-item-title'     => 'Privacy Policy',
            'menu-item-object'    => 'page',
            'menu-item-object-id' => $privacy_page_id,
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
        ) );

        return true;
    }

    return false;
}

// Admin notice after activation
add_action( 'admin_notices', 'ah_ho_legal_pages_admin_notice' );

function ah_ho_legal_pages_admin_notice() {
    $created_data = get_option( 'ah_ho_legal_pages_created' );

    if ( $created_data ) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Legal Pages Setup Complete! 🎉</strong></p>
            <ul>
                <li>✅ Terms and Conditions: <a href="<?php echo get_permalink( $created_data['terms_page_id'] ); ?>" target="_blank">View Page</a> | <a href="<?php echo get_edit_post_link( $created_data['terms_page_id'] ); ?>">Edit</a></li>
                <li>✅ Privacy Policy: <a href="<?php echo get_permalink( $created_data['privacy_page_id'] ); ?>" target="_blank">View Page</a> | <a href="<?php echo get_edit_post_link( $created_data['privacy_page_id'] ); ?>">Edit</a></li>
                <?php if ( isset( $created_data['footer_menu_updated'] ) && $created_data['footer_menu_updated'] ) : ?>
                    <li>✅ Footer menu updated with legal page links</li>
                <?php endif; ?>
            </ul>
            <p><strong>Auto-Configuration Complete:</strong></p>
            <ul>
                <li>✅ Both pages published and live</li>
                <li>✅ WooCommerce Terms page configured</li>
                <li>✅ WordPress Privacy page configured</li>
                <?php if ( isset( $created_data['footer_menu_updated'] ) && $created_data['footer_menu_updated'] ) : ?>
                    <li>✅ Footer menu updated automatically</li>
                <?php else : ?>
                    <li>⚠️ Footer menu: Please add pages manually (Appearance → Menus)</li>
                <?php endif; ?>
            </ul>
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Review both pages to ensure accuracy</li>
                <li>Visit your website footer to verify menu links appear</li>
                <li><strong>You can now deactivate and delete this plugin</strong></li>
            </ol>
        </div>
        <?php

        // Clear the option after showing notice once
        if ( isset( $_GET['activate'] ) ) {
            delete_option( 'ah_ho_legal_pages_created' );
        }
    }
}
