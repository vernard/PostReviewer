<script setup>
import { RouterLink } from 'vue-router';
import Footer from '@/components/Footer.vue';
import { useDarkMode } from '@/composables/useDarkMode';

const { isDark, toggle: toggleDarkMode } = useDarkMode();
const lastUpdated = 'December 11, 2025';
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow dark:shadow-gray-700/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <RouterLink to="/">
                            <img src="/images/post-reviewer-logo.svg" alt="Post Reviewer" class="h-10 shrink-0 dark:hidden" />
                            <img src="/images/post-reviewer-logo-dark.svg" alt="Post Reviewer" class="h-10 shrink-0 hidden dark:block" />
                        </RouterLink>
                    </div>
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <button
                            @click="toggleDarkMode"
                            class="hidden sm:block p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700"
                        >
                            <svg v-if="isDark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                            </svg>
                        </button>
                        <RouterLink to="/login" class="hidden sm:inline text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Login</RouterLink>
                        <RouterLink to="/register" class="inline-flex items-center bg-primary-600 text-white px-3 py-1.5 sm:px-4 sm:py-2 text-sm sm:text-base rounded-md hover:bg-primary-700 whitespace-nowrap h-auto">
                            Get Started
                        </RouterLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-700/30 px-8 py-10">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Terms of Service</h1>
                <p class="text-gray-500 dark:text-gray-400 mb-8">Last updated: {{ lastUpdated }}</p>

                <div class="prose prose-indigo dark:prose-invert max-w-none">
                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">1. Agreement to Terms</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            By accessing or using Post Reviewer ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you disagree with any part of these terms, you may not access the Service.
                        </p>
                        <p class="text-gray-600 dark:text-gray-300">
                            These Terms apply to all visitors, users, and others who access or use the Service. By using the Service, you represent that you are at least 18 years old, or if you are between 13 and 18 years old, that you have your parent or guardian's permission to use the Service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">2. Description of Service</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            Post Reviewer is a software-as-a-service platform that allows users to:
                        </p>
                        <ul class="list-disc list-inside text-gray-600 dark:text-gray-300 space-y-1">
                            <li>Create visual mockups of social media posts for platforms including Facebook and Instagram</li>
                            <li>Upload and manage media assets including images, videos, and brand logos</li>
                            <li>Collaborate with team members on content creation</li>
                            <li>Submit content for approval and manage approval workflows</li>
                            <li>Organize content by brands and campaigns</li>
                        </ul>
                        <p class="text-gray-600 dark:text-gray-300 mt-4">
                            The Service is designed for preview and approval purposes only. We do not publish content directly to social media platforms on your behalf.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">3. User Accounts</h2>

                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2">3.1 Account Creation</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            To use certain features of the Service, you must register for an account. When you register, you agree to provide accurate, current, and complete information and to update such information to keep it accurate, current, and complete.
                        </p>

                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2">3.2 Account Security</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            You are responsible for safeguarding your account credentials and for all activities that occur under your account. You must notify us immediately of any unauthorized use of your account or any other breach of security. We will not be liable for any loss or damage arising from your failure to protect your account credentials.
                        </p>

                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2">3.3 Agency Accounts</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            If you create an account on behalf of an organization or agency, you represent that you have the authority to bind that organization to these Terms. The organization will be responsible for all activities conducted through accounts associated with it.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">4. Acceptable Use Policy</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">You agree not to use the Service to:</p>
                        <ul class="list-disc list-inside text-gray-600 dark:text-gray-300 space-y-1">
                            <li>Upload, post, or transmit any content that is unlawful, harmful, threatening, abusive, harassing, defamatory, vulgar, obscene, or otherwise objectionable</li>
                            <li>Upload content that infringes on any patent, trademark, trade secret, copyright, or other proprietary rights of any party</li>
                            <li>Upload content containing child sexual abuse material or content that exploits minors</li>
                            <li>Impersonate any person or entity, or falsely state or misrepresent your affiliation with a person or entity</li>
                            <li>Engage in any activity that interferes with or disrupts the Service or servers and networks connected to the Service</li>
                            <li>Attempt to gain unauthorized access to any portion of the Service or any other systems or networks</li>
                            <li>Use the Service to send spam, chain letters, or other unsolicited communications</li>
                            <li>Use automated systems, including bots, scrapers, or crawlers, to access the Service without our express permission</li>
                            <li>Violate any applicable laws, regulations, or third-party rights</li>
                        </ul>
                        <p class="text-gray-600 dark:text-gray-300 mt-4">
                            We reserve the right to remove any content that violates these Terms and to terminate accounts of users who repeatedly violate them.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">5. Intellectual Property</h2>

                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2">5.1 Your Content</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            You retain all ownership rights to the content you upload to the Service, including images, videos, logos, and text ("Your Content"). By uploading Your Content, you grant us a limited, non-exclusive, royalty-free license to use, store, display, and reproduce Your Content solely for the purpose of providing the Service to you.
                        </p>

                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2">5.2 Our Content</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            The Service and its original content (excluding Your Content), features, and functionality are and will remain the exclusive property of Post Reviewer and its licensors. The Service is protected by copyright, trademark, and other laws. Our trademarks may not be used in connection with any product or service without our prior written consent.
                        </p>

                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-2">5.3 Feedback</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            If you provide us with any feedback, suggestions, or ideas about the Service, you grant us an unlimited, irrevocable, perpetual, royalty-free license to use and incorporate such feedback for any purpose without any obligation to you.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">6. Payment Terms</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            Certain features of the Service may require payment. If you choose to subscribe to a paid plan:
                        </p>
                        <ul class="list-disc list-inside text-gray-600 dark:text-gray-300 space-y-1">
                            <li>You agree to pay all fees associated with your selected plan</li>
                            <li>Fees are billed in advance on a monthly or annual basis depending on your subscription</li>
                            <li>All fees are non-refundable except as expressly stated in these Terms or required by law</li>
                            <li>We may change our fees upon 30 days' notice, which may be provided through the Service or via email</li>
                            <li>Failure to pay may result in suspension or termination of your account</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">7. Disclaimer of Warranties</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            THE SERVICE IS PROVIDED ON AN "AS IS" AND "AS AVAILABLE" BASIS WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, NON-INFRINGEMENT, OR COURSE OF PERFORMANCE.
                        </p>
                        <p class="text-gray-600 dark:text-gray-300">
                            We do not warrant that (a) the Service will function uninterrupted, secure, or available at any particular time or location; (b) any errors or defects will be corrected; (c) the Service is free of viruses or other harmful components; or (d) the results of using the Service will meet your requirements.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">8. Limitation of Liability</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, IN NO EVENT SHALL POST REVIEWER, ITS AFFILIATES, DIRECTORS, EMPLOYEES, OR AGENTS BE LIABLE FOR ANY INDIRECT, PUNITIVE, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR EXEMPLARY DAMAGES, INCLUDING WITHOUT LIMITATION DAMAGES FOR LOSS OF PROFITS, GOODWILL, USE, DATA, OR OTHER INTANGIBLE LOSSES, ARISING OUT OF OR RELATING TO THE USE OF, OR INABILITY TO USE, THE SERVICE.
                        </p>
                        <p class="text-gray-600 dark:text-gray-300">
                            IN NO EVENT SHALL OUR TOTAL LIABILITY TO YOU FOR ALL CLAIMS ARISING FROM OR RELATING TO THE SERVICE EXCEED THE AMOUNT PAID BY YOU TO US DURING THE TWELVE (12) MONTHS PRIOR TO THE CLAIM, OR ONE HUNDRED DOLLARS ($100), WHICHEVER IS GREATER.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">9. Indemnification</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            You agree to defend, indemnify, and hold harmless Post Reviewer and its officers, directors, employees, contractors, agents, licensors, and suppliers from and against any claims, liabilities, damages, judgments, awards, losses, costs, expenses, or fees (including reasonable attorneys' fees) arising out of or relating to your violation of these Terms or your use of the Service, including but not limited to Your Content, any use of the Service's content and features other than as expressly authorized in these Terms, or your use of any information obtained from the Service.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">10. Termination</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            We may terminate or suspend your account and access to the Service immediately, without prior notice or liability, for any reason, including without limitation if you breach these Terms.
                        </p>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            You may terminate your account at any time by contacting us or using the account deletion feature in the Service settings. Upon termination:
                        </p>
                        <ul class="list-disc list-inside text-gray-600 dark:text-gray-300 space-y-1">
                            <li>Your right to use the Service will immediately cease</li>
                            <li>We may delete Your Content in accordance with our data retention policy</li>
                            <li>Any provisions of these Terms which by their nature should survive termination shall survive, including ownership provisions, warranty disclaimers, indemnity, and limitations of liability</li>
                        </ul>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">11. Governing Law</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            These Terms shall be governed by and construed in accordance with the laws of the jurisdiction in which Post Reviewer is established, without regard to its conflict of law provisions.
                        </p>
                        <p class="text-gray-600 dark:text-gray-300">
                            Any disputes arising from or relating to these Terms or the Service shall be resolved through binding arbitration in accordance with the rules of the applicable arbitration association, except that either party may seek injunctive or other equitable relief in any court of competent jurisdiction.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">12. Changes to Terms</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            We reserve the right to modify or replace these Terms at any time at our sole discretion. If a revision is material, we will provide at least 30 days' notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion. By continuing to access or use our Service after any revisions become effective, you agree to be bound by the revised terms.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">13. Severability</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            If any provision of these Terms is held to be unenforceable or invalid, such provision will be changed and interpreted to accomplish the objectives of such provision to the greatest extent possible under applicable law, and the remaining provisions will continue in full force and effect.
                        </p>
                    </section>

                    <section class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">14. Waiver</h2>
                        <p class="text-gray-600 dark:text-gray-300">
                            Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights. The waiver of any such right or provision will be effective only if in writing and signed by a duly authorized representative of Post Reviewer.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">15. Contact Us</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            If you have any questions about these Terms, please contact us:
                        </p>
                        <ul class="text-gray-600 dark:text-gray-300 space-y-1">
                            <li><strong>Email:</strong> legal@postreviewer.com</li>
                            <li><strong>Support:</strong> support@postreviewer.com</li>
                        </ul>
                    </section>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
