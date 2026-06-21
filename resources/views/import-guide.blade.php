<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Product Import Guide
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Use this guide to prepare CSV files and settle the product-type and scan-config settings.
                </p>
            </div>
            <a href="{{ route('product-import.template') }}" class="rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-400">
                Download Sample Template
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">1. CSV template layout</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        The importer expects lowercase headers. Standard columns are always supported, and dynamic columns must match the exact <code>field_name</code> from the product type setup.
                    </p>

                    <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Column</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Required</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">code</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Yes</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Must be unique for each product.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">name</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Yes</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Displayed product name.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">category_name</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Yes</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Creates the category if it does not exist.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">sub_category_name</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">No</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Optional subcategory under the category.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">location_code</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">No</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Creates a location when needed.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">barcode</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">No</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Optional barcode value.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">qr_code</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">No</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Optional QR code value.</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">description</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">No</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Free-form notes or description.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 rounded-2xl bg-slate-950 p-4 text-sm text-slate-100">
                        <p class="mb-2 font-semibold text-white">Example row</p>
                        <pre class="overflow-x-auto text-xs leading-6">code,name,category_name,sub_category_name,location_code,barcode,qr_code,description
PRD-1001,Sample Product,Default Category,General,LOC-001,1234567890123,QR-1001,Imported from the template</pre>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-900/40 dark:bg-amber-950/30">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">2. Product type setup</h3>
                        <ol class="mt-3 space-y-3 text-sm text-gray-700 dark:text-gray-300">
                            <li>1. Create the product type first.</li>
                            <li>2. Add dynamic fields under the product type.</li>
                            <li>3. Use snake_case names like <code>weight_g</code> or <code>serial_no</code>.</li>
                            <li>4. Keep the fields active if they should appear in imports and scans.</li>
                        </ol>
                    </div>

                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/30">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">3. Scan config setup</h3>
                        <ol class="mt-3 space-y-3 text-sm text-gray-700 dark:text-gray-300">
                            <li>1. Add a scan config for the same product type.</li>
                            <li>2. Choose the field name to compare.</li>
                            <li>3. Turn on <code>Compare Expected?</code> when the field must match the master product data.</li>
                            <li>4. Use <code>Required</code> when the checker must enter a value.</li>
                            <li>5. Set tolerance for numeric checks when small variation is allowed.</li>
                        </ol>
                    </div>

                    <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">4. Import flow</h3>
                        <ol class="mt-3 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                            <li>1. Download the sample template.</li>
                            <li>2. Fill the standard columns.</li>
                            <li>3. Add product-type-specific columns using the exact field names.</li>
                            <li>4. Upload the CSV from the Products page.</li>
                            <li>5. Review import logs if any row fails.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
