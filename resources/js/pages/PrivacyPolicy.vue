<script setup>
import { RouterLink } from 'vue-router';
import Footer from '@/components/Footer.vue';

const lastUpdated = 'December 11, 2025';
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <RouterLink to="/" class="text-xl font-bold text-gray-900">Post Reviewer</RouterLink>
                    </div>
                    <div class="flex items-center space-x-4">
                        <RouterLink to="/login" class="text-gray-600 hover:text-gray-900">Login</RouterLink>
                        <RouterLink to="/register" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                            Get Started
                        </RouterLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow px-8 py-10">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Privacy Policy</h1>
                <p class="text-gray-500 mb-8">Last updated: {{ lastUpdated }}</p>

                <div class="prose prose-indigo max-w-none">
                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Introduction</h2>
                        <p class="text-gray-600 mb-4">
                            Welcome to Post Reviewer ("we," "our," or "us"). We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our social media mockup creation and approval platform.
                        </p>
                        <p class="text-gray-600">
                            By using Post Reviewer, you agree to the collection and use of information in accordance with this policy. If you do not agree with the terms of this privacy policy, please do not access or use our services.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Information We Collect</h2>

                        <h3 class="text-lg font-medium text-gray-800 mb-2">2.1 Account Information</h3>
                        <p class="text-gray-600 mb-4">
                            When you create an account, we collect:
                        </p>
                        <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
                            <li>Your name and email address</li>
                            <li>Your organization or agency name</li>
                            <li>Password (stored in encrypted form)</li>
                            <li>Profile information you choose to provide</li>
                        </ul>

                        <h3 class="text-lg font-medium text-gray-800 mb-2">2.2 Content You Upload</h3>
                        <p class="text-gray-600 mb-4">
                            To provide our mockup services, we store:
                        </p>
                        <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
                            <li>Images and videos you upload for mockups</li>
                            <li>Brand logos and assets</li>
                            <li>Captions and text content for posts</li>
                            <li>Comments and feedback on approval requests</li>
                        </ul>

                        <h3 class="text-lg font-medium text-gray-800 mb-2">2.3 Usage Data</h3>
                        <p class="text-gray-600 mb-4">
                            We automatically collect certain information when you use our service:
                        </p>
                        <ul class="list-disc list-inside text-gray-600 mb-4 space-y-1">
                            <li>IP address and browser type</li>
                            <li>Device information and operating system</li>
                            <li>Pages visited and features used</li>
                            <li>Time and date of your visits</li>
                            <li>Referring website addresses</li>
                        </ul>

                        <h3 class="text-lg font-medium text-gray-800 mb-2">2.4 Cookies and Tracking</h3>
                        <p class="text-gray-600">
                            We use cookies and similar tracking technologies to maintain your session, remember your preferences, and analyze how our service is used. You can control cookie settings through your browser preferences.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">3. How We Use Your Information</h2>
                        <p class="text-gray-600 mb-4">We use the information we collect to:</p>
                        <ul class="list-disc list-inside text-gray-600 space-y-1">
                            <li>Provide, operate, and maintain our services</li>
                            <li>Create and manage your account</li>
                            <li>Process and display your mockups</li>
                            <li>Facilitate the approval workflow between team members</li>
                            <li>Send you service-related notifications and updates</li>
                            <li>Respond to your comments, questions, and support requests</li>
                            <li>Monitor and analyze usage patterns to improve our service</li>
                            <li>Detect, prevent, and address technical issues or fraud</li>
                            <li>Comply with legal obligations</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Information Sharing and Disclosure</h2>
                        <p class="text-gray-600 mb-4">We do not sell your personal information. We may share your information in the following circumstances:</p>

                        <h3 class="text-lg font-medium text-gray-800 mb-2">4.1 Within Your Organization</h3>
                        <p class="text-gray-600 mb-4">
                            Content and activity within your agency/organization is visible to other members of your team as part of the collaborative approval workflow.
                        </p>

                        <h3 class="text-lg font-medium text-gray-800 mb-2">4.2 Service Providers</h3>
                        <p class="text-gray-600 mb-4">
                            We may share information with third-party vendors who perform services on our behalf, such as cloud hosting, analytics, and email delivery. These providers are contractually obligated to protect your information.
                        </p>

                        <h3 class="text-lg font-medium text-gray-800 mb-2">4.3 Legal Requirements</h3>
                        <p class="text-gray-600">
                            We may disclose your information if required by law, court order, or government request, or if we believe disclosure is necessary to protect our rights, your safety, or the safety of others.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Data Retention</h2>
                        <p class="text-gray-600 mb-4">
                            We retain your personal information for as long as your account is active or as needed to provide you services. Specifically:
                        </p>
                        <ul class="list-disc list-inside text-gray-600 space-y-1">
                            <li>Account information is retained until you delete your account</li>
                            <li>Uploaded content is retained until you delete it or close your account</li>
                            <li>Usage logs are typically retained for 90 days</li>
                            <li>Backup copies may persist for up to 30 days after deletion</li>
                        </ul>
                        <p class="text-gray-600 mt-4">
                            Upon account deletion, we will delete or anonymize your personal information within 30 days, except where we are required to retain it for legal or legitimate business purposes.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Your Rights and Choices</h2>
                        <p class="text-gray-600 mb-4">Depending on your location, you may have the following rights:</p>
                        <ul class="list-disc list-inside text-gray-600 space-y-1">
                            <li><strong>Access:</strong> Request a copy of the personal information we hold about you</li>
                            <li><strong>Correction:</strong> Request that we correct inaccurate or incomplete information</li>
                            <li><strong>Deletion:</strong> Request that we delete your personal information</li>
                            <li><strong>Portability:</strong> Request a copy of your data in a portable format</li>
                            <li><strong>Objection:</strong> Object to certain processing of your information</li>
                            <li><strong>Withdrawal:</strong> Withdraw consent where processing is based on consent</li>
                        </ul>
                        <p class="text-gray-600 mt-4">
                            To exercise these rights, please contact us at privacy@postreviewer.com. We will respond to your request within 30 days.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Data Security</h2>
                        <p class="text-gray-600 mb-4">
                            We implement appropriate technical and organizational security measures to protect your personal information, including:
                        </p>
                        <ul class="list-disc list-inside text-gray-600 space-y-1">
                            <li>Encryption of data in transit using TLS/SSL</li>
                            <li>Encryption of sensitive data at rest</li>
                            <li>Regular security assessments and updates</li>
                            <li>Access controls and authentication requirements</li>
                            <li>Secure cloud infrastructure with reputable providers</li>
                        </ul>
                        <p class="text-gray-600 mt-4">
                            However, no method of transmission over the Internet or electronic storage is 100% secure. While we strive to protect your information, we cannot guarantee absolute security.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Children's Privacy</h2>
                        <p class="text-gray-600">
                            Post Reviewer is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If you are a parent or guardian and believe your child has provided us with personal information, please contact us immediately, and we will take steps to delete such information.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">9. International Data Transfers</h2>
                        <p class="text-gray-600">
                            Your information may be transferred to and processed in countries other than your country of residence. These countries may have data protection laws that are different from the laws of your country. We take appropriate safeguards to ensure that your personal information remains protected in accordance with this Privacy Policy.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">10. Changes to This Policy</h2>
                        <p class="text-gray-600">
                            We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new Privacy Policy on this page and updating the "Last updated" date. We encourage you to review this Privacy Policy periodically for any changes. Your continued use of the service after any modifications indicates your acceptance of the updated policy.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">11. Contact Us</h2>
                        <p class="text-gray-600 mb-4">
                            If you have any questions about this Privacy Policy or our data practices, please contact us:
                        </p>
                        <ul class="text-gray-600 space-y-1">
                            <li><strong>Email:</strong> privacy@postreviewer.com</li>
                            <li><strong>Support:</strong> support@postreviewer.com</li>
                        </ul>
                    </section>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
