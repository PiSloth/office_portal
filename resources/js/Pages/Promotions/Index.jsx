import React, { useState, useEffect, useRef } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { 
    Search, 
    Upload, 
    Columns, 
    Check, 
    ArrowLeft, 
    FileSpreadsheet, 
    X, 
    AlertCircle, 
    CheckCircle2, 
    ChevronLeft, 
    ChevronRight,
    RefreshCw,
    ShoppingCart,
    BookmarkCheck,
    Bookmark,
    Sparkles,
    Trash2,
    Copy,
    Tag,
    Building2,
    CheckSquare,
    Square,
    Receipt,
    RotateCcw
} from 'lucide-react';

const ALL_COLUMNS = [
    { key: 'lot_serial_number', label: 'Lot/Serial', defaultVisible: true },
    { key: 'barcode', label: 'Barcode', defaultVisible: true },
    { key: 'product', label: 'Product', defaultVisible: true },
    { key: 'branch_name', label: 'Branch Name', defaultVisible: true },
    { key: 'product_subgroup_name', label: 'Subgroup', defaultVisible: false },
    { key: 'weight', label: 'Weight (g)', defaultVisible: false },
    { key: 'gold_weight', label: 'Gold Weight (g)', defaultVisible: true },
    { key: 'dg_weight', label: 'D/G WT', defaultVisible: false },
    { key: 'addition_wastage', label: 'Add Wastage', defaultVisible: false },
    { key: 'labor_fee', label: 'Labour Fee', defaultVisible: true },
    { key: 'total_wastage', label: 'Total Wastage', defaultVisible: true },
    { key: 'promotion_price', label: 'Promotion Price', defaultVisible: true },
    { key: 'internal_reference', label: 'Internal Ref', defaultVisible: false },
    { key: 'actions', label: 'Actions / လုပ်ဆောင်ချက်', defaultVisible: true },
];

export default function Index({ 
    items, 
    branches = [], 
    filters = {}, 
    canImport, 
    latestGoldPrice = 0, 
    latestPriceHistoryId = null, 
    latestPriceUpdatedAt = null, 
    importErrors = [] 
}) {
    const { flash, auth } = usePage().props;
    const currentUser = auth?.user;
    const [search, setSearch] = useState(filters.search || '');
    const [selectedBranchId, setSelectedBranchId] = useState(filters.branch_id || '');
    const [selectedSoldStatus, setSelectedSoldStatus] = useState(filters.sold_status || 'all');
    const [isColumnDropdownOpen, setIsColumnDropdownOpen] = useState(false);
    const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
    const [uploadFile, setUploadFile] = useState(null);
    const [isUploading, setIsUploading] = useState(false);
    const [uploadError, setUploadError] = useState('');
    const [currentImportErrors, setCurrentImportErrors] = useState(importErrors || []);
    const [isImportErrorsModalOpen, setIsImportErrorsModalOpen] = useState(importErrors && importErrors.length > 0);
    const columnDropdownRef = useRef(null);

    // Live real-time gold price state
    const [currentGoldPrice, setCurrentGoldPrice] = useState(latestGoldPrice || 0);
    const [currentPriceHistoryId, setCurrentPriceHistoryId] = useState(latestPriceHistoryId);
    const [currentPriceUpdatedAt, setCurrentPriceUpdatedAt] = useState(latestPriceUpdatedAt);
    const [priceUpdatedNotification, setPriceUpdatedNotification] = useState(false);

    // Sync state when props change
    useEffect(() => {
        if (latestGoldPrice) {
            setCurrentGoldPrice(latestGoldPrice);
            setCurrentPriceHistoryId(latestPriceHistoryId);
            setCurrentPriceUpdatedAt(latestPriceUpdatedAt);
        }
    }, [latestGoldPrice, latestPriceHistoryId, latestPriceUpdatedAt]);

    // Real-time polling for daily gold price (checks every 5 seconds)
    useEffect(() => {
        let isMounted = true;

        const checkLatestPrice = async () => {
            try {
                const response = await fetch('/promotions/latest-price', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) return;

                const data = await response.json();
                if (!isMounted) return;

                const newGoldPrice = parseFloat(data.latest_gold_price) || 0;
                const newHistoryId = data.latest_price_history_id;
                const newUpdatedAt = data.latest_price_updated_at;

                setCurrentGoldPrice((prevPrice) => {
                    if (newGoldPrice > 0 && Math.round(newGoldPrice) !== Math.round(prevPrice)) {
                        setCurrentPriceHistoryId(newHistoryId);
                        setCurrentPriceUpdatedAt(newUpdatedAt);
                        setPriceUpdatedNotification(true);
                        setTimeout(() => {
                            if (isMounted) setPriceUpdatedNotification(false);
                        }, 4000);
                        return newGoldPrice;
                    }
                    return prevPrice;
                });
            } catch (err) {
                // Silently continue polling on network hiccups
            }
        };

        const interval = setInterval(checkLatestPrice, 5000);
        return () => {
            isMounted = false;
            clearInterval(interval);
        };
    }, []);

    useEffect(() => {
        if (importErrors && importErrors.length > 0) {
            setCurrentImportErrors(importErrors);
            setIsImportErrorsModalOpen(true);
        }
    }, [importErrors]);

    // Cart state
    const [cartItems, setCartItems] = useState(() => {
        try {
            const saved = localStorage.getItem('promotion_cart_items');
            return saved ? JSON.parse(saved) : [];
        } catch (e) {
            return [];
        }
    });
    const [isCartModalOpen, setIsCartModalOpen] = useState(false);
    const [voucherNumber, setVoucherNumber] = useState('');
    const [voucherError, setVoucherError] = useState('');
    const [isSavingSold, setIsSavingSold] = useState(false);
    const [copySuccessNotification, setCopySuccessNotification] = useState('');

    // Cancel Sold Modal state
    const [isCancelModalOpen, setIsCancelModalOpen] = useState(false);
    const [cancelVoucherNumber, setCancelVoucherNumber] = useState('');
    const [cancelVoucherItems, setCancelVoucherItems] = useState([]);
    const [selectedCancelItemIds, setSelectedCancelItemIds] = useState([]);
    const [cancelReason, setCancelReason] = useState('');
    const [cancelError, setCancelError] = useState('');
    const [isLoadingVoucherItems, setIsLoadingVoucherItems] = useState(false);
    const [isSubmittingCancel, setIsSubmittingCancel] = useState(false);

    // VR Search Modal state
    const [isVrSearchModalOpen, setIsVrSearchModalOpen] = useState(false);
    const [vrSearchNumber, setVrSearchNumber] = useState('');
    const [isSearchingVr, setIsSearchingVr] = useState(false);
    const [vrSearchError, setVrSearchError] = useState('');
    const [vrSearchResult, setVrSearchResult] = useState(null); // { reference_number: '', items: [] }
    const [vrCopiedSuccess, setVrCopiedSuccess] = useState(false);

    useEffect(() => {
        try {
            localStorage.setItem('promotion_cart_items', JSON.stringify(cartItems));
        } catch (e) {
            console.error(e);
        }
    }, [cartItems]);

    // Load saved visible columns or use defaults
    const [visibleColumns, setVisibleColumns] = useState(() => {
        try {
            const saved = localStorage.getItem('promotion_items_visible_columns');
            if (saved) {
                return JSON.parse(saved);
            }
        } catch (e) {
            console.error(e);
        }
        return ALL_COLUMNS.reduce((acc, col) => {
            acc[col.key] = col.defaultVisible;
            return acc;
        }, {});
    });

    const toggleColumn = (key) => {
        setVisibleColumns((prev) => {
            const updated = { ...prev, [key]: !prev[key] };
            localStorage.setItem('promotion_items_visible_columns', JSON.stringify(updated));
            return updated;
        });
    };

    // Close column dropdown on outside click
    useEffect(() => {
        function handleClickOutside(event) {
            if (columnDropdownRef.current && !columnDropdownRef.current.contains(event.target)) {
                setIsColumnDropdownOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Apply filters helper
    const applyFilters = (newParams) => {
        const queryParams = {
            search: search || undefined,
            branch_id: selectedBranchId || undefined,
            sold_status: selectedSoldStatus !== 'all' ? selectedSoldStatus : undefined,
            ...newParams,
        };

        // Remove undefined / empty values
        Object.keys(queryParams).forEach((k) => {
            if (queryParams[k] === undefined || queryParams[k] === '' || queryParams[k] === 'all') {
                delete queryParams[k];
            }
        });

        router.get('/promotions', queryParams, {
            preserveState: true,
            replace: true,
        });
    };

    // Live debounced search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters.search || '')) {
                applyFilters({ search: search || undefined });
            }
        }, 350);

        return () => clearTimeout(timer);
    }, [search]);

    const handleBranchChange = (e) => {
        const branchId = e.target.value;
        setSelectedBranchId(branchId);
        applyFilters({ branch_id: branchId || undefined });
    };

    const handleToggleSoldStatus = () => {
        const nextStatus = selectedSoldStatus === 'sold' ? 'all' : 'sold';
        setSelectedSoldStatus(nextStatus);
        applyFilters({ sold_status: nextStatus !== 'all' ? nextStatus : undefined });
    };

    const handleUploadSubmit = (e) => {
        e.preventDefault();
        if (!uploadFile) {
            setUploadError('Please select a .xlsx or .csv file to upload.');
            return;
        }

        setIsUploading(true);
        setUploadError('');

        const formData = new FormData();
        formData.append('file', uploadFile);

        router.post('/promotions/import', formData, {
            onSuccess: () => {
                setIsUploading(false);
                setIsUploadModalOpen(false);
                setUploadFile(null);
            },
            onError: (err) => {
                setIsUploading(false);
                setUploadError(err.file || 'Failed to import file. Please check file format.');
            },
        });
    };

    const formatNumber = (val, decimals = 2) => {
        if (val === null || val === undefined || val === '') return '-';
        const num = parseFloat(val);
        if (isNaN(num)) return val;
        return num.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    };

    const roundDownToTens = (amount) => {
        if (!amount || isNaN(amount)) return 0;
        return Math.floor(amount / 10) * 10;
    };

    const formatRelativeDateTime = (dateStr) => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return '';

        const now = new Date();
        const isToday =
            date.getFullYear() === now.getFullYear() &&
            date.getMonth() === now.getMonth() &&
            date.getDate() === now.getDate();

        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        const isYesterday =
            date.getFullYear() === yesterday.getFullYear() &&
            date.getMonth() === yesterday.getMonth() &&
            date.getDate() === yesterday.getDate();

        const timeString = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        if (isToday) {
            return `Today at ${timeString}`;
        }
        if (isYesterday) {
            return `Yesterday at ${timeString}`;
        }

        const dateFormatted = date.toLocaleDateString([], {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
        return `${dateFormatted} at ${timeString}`;
    };

    const calculatePromotionPrice = (totalWastage, laborFee, goldPrice = currentGoldPrice) => {
        const rawTotalWastage = parseFloat(totalWastage);
        const wastage = (!isNaN(rawTotalWastage) && rawTotalWastage > 0) ? (rawTotalWastage / 2) : 0;
        const labor = parseFloat(laborFee);
        let price = 0;

        // If (total_wastage / 2) is > 0, calculate from (total_wastage / 2) and gold price only (do not add labor fee / 2)
        if (wastage > 0) {
            if (goldPrice > 0) {
                price = wastage * (goldPrice / 16.606);
            }
        } 
        // If total_wastage is 0 (or empty/negative) and labor fee > 0, price is labor fee / 2
        else if (!isNaN(labor) && labor > 0) {
            price = labor / 2;
        }

        if (price <= 0) return 0;

        // Round down to 2nd digit (nearest 10, e.g., 40052 -> 40050)
        return roundDownToTens(price);
    };

    // Cart actions
    const isInCart = (id) => cartItems.some((item) => item.id === id);

    const handleBookmark = (item) => {
        if (item.sold_item) return;
        if (isInCart(item.id)) {
            setCartItems((prev) => prev.filter((i) => i.id !== item.id));
        } else {
            const promoPrice = calculatePromotionPrice(item.total_wastage, item.labor_fee);
            setCartItems((prev) => [
                ...prev,
                {
                    id: item.id,
                    barcode: item.barcode,
                    product: item.product,
                    lot_serial_number: item.lot_serial_number,
                    product_subgroup_name: item.product_subgroup_name,
                    addition_wastage: item.addition_wastage,
                    total_wastage: item.total_wastage,
                    labor_fee: item.labor_fee,
                    promotion_price: promoPrice,
                },
            ]);
        }
    };

    const handleDirectUse = (item) => {
        if (item.sold_item) return;
        if (!isInCart(item.id)) {
            const promoPrice = calculatePromotionPrice(item.total_wastage, item.labor_fee);
            setCartItems((prev) => [
                ...prev,
                {
                    id: item.id,
                    barcode: item.barcode,
                    product: item.product,
                    lot_serial_number: item.lot_serial_number,
                    product_subgroup_name: item.product_subgroup_name,
                    addition_wastage: item.addition_wastage,
                    total_wastage: item.total_wastage,
                    labor_fee: item.labor_fee,
                    promotion_price: promoPrice,
                },
            ]);
        }
        setIsCartModalOpen(true);
    };

    const removeFromCart = (id) => {
        setCartItems((prev) => prev.filter((i) => i.id !== id));
    };

    // Calculate cart items dynamically based on currentGoldPrice
    const dynamicCartItems = cartItems.map((cItem) => {
        const livePrice = (cItem.total_wastage !== undefined && cItem.total_wastage !== null)
            ? calculatePromotionPrice(cItem.total_wastage, cItem.labor_fee)
            : ((cItem.addition_wastage !== undefined && cItem.addition_wastage !== null)
                ? calculatePromotionPrice(cItem.addition_wastage, cItem.labor_fee)
                : (parseFloat(cItem.promotion_price) || 0));

        return {
            ...cItem,
            live_price: livePrice,
        };
    });

    const grandTotal = dynamicCartItems.reduce((acc, item) => acc + (parseFloat(item.live_price) || 0), 0);

    const handleSaveAndCopy = async (e) => {
        e.preventDefault();
        if (!voucherNumber.trim()) {
            setVoucherError('Please enter a sale voucher number (ဘောက်ချာနံပါတ် ထည့်သွင်းပါ)');
            return;
        }

        if (dynamicCartItems.length === 0) {
            setVoucherError('Cart is empty.');
            return;
        }

        setIsSavingSold(true);
        setVoucherError('');

        // Copy Grand Total to clipboard rounded down to tens (e.g., 40052 -> 40050)
        const finalGrandTotal = roundDownToTens(grandTotal);
        try {
            await navigator.clipboard.writeText(finalGrandTotal.toString());
            setCopySuccessNotification(`Copied Grand Total: ${finalGrandTotal.toLocaleString()} MMK`);
            setTimeout(() => setCopySuccessNotification(''), 4000);
        } catch (err) {
            console.error('Clipboard copy failed:', err);
        }

        router.post(
            '/promotions/sold',
            {
                reference_number: voucherNumber.trim(),
                daily_price_history_id: currentPriceHistoryId,
                items: dynamicCartItems.map((item) => ({
                    id: item.id,
                    promotion_price: roundDownToTens(item.live_price),
                })),
            },
            {
                onSuccess: () => {
                    setIsSavingSold(false);
                    setIsCartModalOpen(false);
                    setCartItems([]);
                    setVoucherNumber('');
                },
                onError: (err) => {
                    setIsSavingSold(false);
                    setVoucherError(err.reference_number || err.message || 'Failed to save sold promotion items.');
                },
            }
        );
    };

    const handleOpenCancelModalByVoucher = async (refNumber) => {
        if (!refNumber) return;
        setCancelVoucherNumber(refNumber);
        setCancelReason('');
        setCancelError('');
        setIsLoadingVoucherItems(true);
        setIsCancelModalOpen(true);

        try {
            const res = await fetch(`/promotions/voucher/${encodeURIComponent(refNumber)}`);
            const data = await res.json();
            const itemsList = data.items || [];
            setCancelVoucherItems(itemsList);
            // Auto-select all items with this voucher by default
            setSelectedCancelItemIds(itemsList.map((i) => i.id));
        } catch (err) {
            console.error('Error fetching voucher items:', err);
            setCancelError('Failed to load items for this voucher number.');
        } finally {
            setIsLoadingVoucherItems(false);
        }
    };

    // Open Cancel Sold Modal for an item
    const handleOpenCancelModal = (item) => {
        if (!item.sold_item) return;
        handleOpenCancelModalByVoucher(item.sold_item.reference_number);
    };

    // VR Search Modal Handlers
    const handleSearchVr = async (e) => {
        if (e) e.preventDefault();
        const query = vrSearchNumber.trim();
        if (!query) {
            setVrSearchError('Please enter an invoice or voucher number (ဘောက်ချာနံပါတ် ထည့်သွင်းပါ).');
            return;
        }

        setIsSearchingVr(true);
        setVrSearchError('');
        setVrSearchResult(null);
        setVrCopiedSuccess(false);

        try {
            const res = await fetch(`/promotions/voucher/${encodeURIComponent(query)}`);
            if (!res.ok) throw new Error('Failed to fetch voucher items');
            const data = await res.json();
            setVrSearchResult(data);
        } catch (err) {
            console.error('Error fetching VR items:', err);
            setVrSearchError('Failed to load items for this voucher number. Please try again.');
        } finally {
            setIsSearchingVr(false);
        }
    };

    const handleCopyVrGrandTotal = async (amount) => {
        if (amount === undefined || amount === null) return;
        try {
            await navigator.clipboard.writeText(amount.toString());
            setVrCopiedSuccess(true);
            setTimeout(() => setVrCopiedSuccess(false), 3000);
        } catch (err) {
            console.error('Clipboard copy failed:', err);
        }
    };

    const handleTriggerCancelFromVr = () => {
        if (!vrSearchResult || !vrSearchResult.reference_number) return;
        const refNum = vrSearchResult.reference_number;
        setIsVrSearchModalOpen(false);
        handleOpenCancelModalByVoucher(refNum);
    };

    const toggleCancelItemSelection = (id) => {
        setSelectedCancelItemIds((prev) => 
            prev.includes(id) ? prev.filter((itemId) => itemId !== id) : [...prev, id]
        );
    };

    const handleCancelSubmit = (e) => {
        e.preventDefault();
        if (selectedCancelItemIds.length === 0) {
            setCancelError('Please select at least one item to cancel.');
            return;
        }

        if (!cancelReason.trim()) {
            setCancelError('Please enter a reason for cancellation (အကြောင်းပြချက် ထည့်သွင်းပါ).');
            return;
        }

        setIsSubmittingCancel(true);
        setCancelError('');

        router.post(
            '/promotions/sold/cancel',
            {
                sold_item_ids: selectedCancelItemIds,
                reason: cancelReason.trim(),
            },
            {
                onSuccess: () => {
                    setIsSubmittingCancel(false);
                    setIsCancelModalOpen(false);
                    setCancelVoucherItems([]);
                    setSelectedCancelItemIds([]);
                    setCancelReason('');
                },
                onError: (err) => {
                    setIsSubmittingCancel(false);
                    setCancelError(err.reason || err.message || 'Failed to cancel sold items.');
                },
            }
        );
    };

    return (
        <div className="min-h-screen bg-slate-100 text-slate-800 flex flex-col font-sans">
            <Head title="Promotion Items" />

            {/* Navigation Bar */}
            <header className="border-b border-slate-200 bg-white/95 backdrop-blur sticky top-0 z-30 shadow-sm">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <a
                            href="/dashboard"
                            className="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 transition px-3 py-1.5 rounded-lg hover:bg-slate-100"
                        >
                            <ArrowLeft className="w-4 h-4 text-slate-500" />
                            <span>Dashboard</span>
                        </a>
                        <div className="h-4 w-px bg-slate-300 hidden sm:block"></div>
                        <h1 className="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            <span className="p-1.5 rounded-lg bg-purple-100 text-purple-700 border border-purple-200">
                                <FileSpreadsheet className="w-4 h-4" />
                            </span>
                            Promotion Items
                        </h1>
                    </div>

                    <div className="flex items-center gap-3">
                        {currentGoldPrice > 0 && (() => {
                            const convertedGoldPrice = (currentGoldPrice / 16.606) * 16.329;
                            const formattedDate = formatRelativeDateTime(currentPriceUpdatedAt);

                            return (
                                <div className={`hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-xl border text-xs font-medium shadow-sm transition-all duration-300 ${
                                    priceUpdatedNotification 
                                        ? 'bg-emerald-100 border-emerald-400 text-emerald-900 ring-2 ring-emerald-400/50' 
                                        : 'bg-amber-50 border border-amber-200 text-amber-800'
                                }`}>
                                    <span className={`w-2 h-2 rounded-full shrink-0 ${
                                        priceUpdatedNotification ? 'bg-emerald-600 animate-ping' : 'bg-amber-500 animate-pulse'
                                    }`}></span>
                                    <div className="flex flex-col sm:flex-row sm:items-center sm:gap-2">
                                        <span>
                                            Gold Price: <strong className="text-amber-950 font-semibold">{Math.round(convertedGoldPrice).toLocaleString()}</strong> MMK
                                        </span>
                                        {formattedDate && (
                                            <span className="text-[11px] text-amber-800 font-normal border-l border-amber-300 pl-2">
                                                {formattedDate}
                                            </span>
                                        )}
                                        {priceUpdatedNotification && (
                                            <span className="text-[10px] font-bold uppercase text-emerald-700 bg-emerald-200/60 px-1.5 py-0.5 rounded ml-1 animate-pulse">
                                                Live Updated
                                            </span>
                                        )}
                                    </div>
                                </div>
                            );
                        })()}

                        {/* VR Search Trigger Button */}
                        <button
                            type="button"
                            onClick={() => {
                                setIsVrSearchModalOpen(true);
                                setVrSearchError('');
                            }}
                            className="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 hover:text-slate-900 text-sm font-semibold shadow-sm transition"
                            title="Search sold items and historical prices by voucher / invoice number"
                        >
                            <Receipt className="w-4 h-4 text-purple-600" />
                            <span className="hidden sm:inline">VR Search</span>
                        </button>

                        {/* Cart Trigger Button */}
                        <button
                            type="button"
                            onClick={() => setIsCartModalOpen(true)}
                            className="relative inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 hover:text-slate-900 text-sm font-semibold shadow-sm transition"
                        >
                            <ShoppingCart className="w-4 h-4 text-purple-600" />
                            <span className="hidden sm:inline">Cart</span>
                            {cartItems.length > 0 && (
                                <span className="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold bg-emerald-600 text-white rounded-full min-w-5 shadow-sm">
                                    {cartItems.length}
                                </span>
                            )}
                        </button>

                        {canImport && (
                            <button
                                type="button"
                                onClick={() => {
                                    setUploadError('');
                                    setIsUploadModalOpen(true);
                                }}
                                className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold shadow-md shadow-purple-600/20 transition"
                            >
                                <Upload className="w-4 h-4" />
                                <span>Import Excel</span>
                            </button>
                        )}
                    </div>
                </div>
            </header>

            {/* Main Content Area */}
            <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
                
                {/* Copy success toast notification */}
                {copySuccessNotification && (
                    <div className="p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 flex items-center gap-3 shadow-md animate-in fade-in">
                        <Copy className="w-5 h-5 text-amber-600 shrink-0" />
                        <span className="text-sm font-medium">{copySuccessNotification}</span>
                    </div>
                )}

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="p-4 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-900 flex items-start gap-3 shadow-sm">
                        <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
                        <div>
                            <p className="font-semibold text-emerald-950">Success</p>
                            <p className="text-sm mt-0.5 text-emerald-800">{flash.success}</p>
                        </div>
                    </div>
                )}
                {flash?.error && (
                    <div className="p-4 rounded-2xl bg-rose-50 border border-rose-300 text-rose-900 flex items-start gap-3 shadow-sm">
                        <AlertCircle className="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                        <div>
                            <p className="font-semibold text-rose-950">Notice</p>
                            <p className="text-sm mt-0.5 text-rose-800">{flash.error}</p>
                        </div>
                    </div>
                )}

                {/* Filter and Control Bar */}
                <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    {/* Search Input for Barcode, Lot/Serial & Voucher */}
                    <div className="relative flex-1 max-w-md">
                        <div className="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none flex items-center justify-center">
                            <Search className="w-4 h-4 text-slate-400" />
                        </div>
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search barcode, lot/serial, or voucher number..."
                            className="w-full pl-10 pr-10 py-2.5 rounded-xl bg-white border border-slate-300 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition leading-normal"
                        />
                        {search && (
                            <div className="absolute right-2.5 top-1/2 -translate-y-1/2 flex items-center justify-center">
                                <button
                                    type="button"
                                    onClick={() => setSearch('')}
                                    className="text-slate-400 hover:text-slate-600 p-1 rounded-md transition flex items-center justify-center"
                                    title="Clear search"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>
                        )}
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        {/* Branch Filter Select */}
                        <div className="relative">
                            <div className="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 flex items-center justify-center">
                                <Building2 className="w-4 h-4 text-slate-400" />
                            </div>
                            <select
                                value={selectedBranchId}
                                onChange={handleBranchChange}
                                className="pl-9 pr-8 py-2.5 rounded-xl bg-white border border-slate-300 text-sm font-medium text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition cursor-pointer leading-normal"
                            >
                                <option value="">All Branches (အားလုံး)</option>
                                {branches.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.name} {b.code ? `(${b.code})` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Check button to see Sold Items */}
                        <button
                            type="button"
                            onClick={handleToggleSoldStatus}
                            title={selectedSoldStatus === 'sold' ? 'Click to show all items' : 'Click to see sold items only'}
                            className={`inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm ${
                                selectedSoldStatus === 'sold'
                                    ? 'bg-amber-100 text-amber-900 border border-amber-300 hover:bg-amber-200 ring-2 ring-amber-400/40'
                                    : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 hover:text-slate-900'
                            }`}
                        >
                            {selectedSoldStatus === 'sold' ? (
                                <CheckSquare className="w-4 h-4 text-amber-700" />
                            ) : (
                                <Square className="w-4 h-4 text-slate-400" />
                            )}
                            <span>Sold Items Only (ရောင်းပြီးစာရင်း)</span>
                            {selectedSoldStatus === 'sold' && (
                                <span className="w-2 h-2 rounded-full bg-amber-600 animate-pulse"></span>
                            )}
                        </button>

                        {/* Table Column Collapse / Toggle Dropdown */}
                        <div className="relative" ref={columnDropdownRef}>
                            <button
                                type="button"
                                onClick={() => setIsColumnDropdownOpen(!isColumnDropdownOpen)}
                                className="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-sm font-medium text-slate-700 shadow-sm transition"
                            >
                                <Columns className="w-4 h-4 text-slate-500" />
                                <span>Columns</span>
                            </button>

                            {isColumnDropdownOpen && (
                                <div className="absolute right-0 mt-2 w-64 rounded-2xl bg-white border border-slate-200 shadow-xl py-2 z-40 max-h-80 overflow-y-auto">
                                    <div className="px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100 mb-1">
                                        Toggle Table Columns
                                    </div>
                                    {ALL_COLUMNS.map((col) => {
                                        const isChecked = !!visibleColumns[col.key];
                                        return (
                                            <button
                                                key={col.key}
                                                type="button"
                                                onClick={() => toggleColumn(col.key)}
                                                className="w-full flex items-center justify-between px-3 py-2 text-sm text-left hover:bg-purple-50 text-slate-700 hover:text-purple-900 transition"
                                            >
                                                <span>{col.label}</span>
                                                <div
                                                    className={`w-4 h-4 rounded border flex items-center justify-center ${
                                                        isChecked
                                                            ? 'bg-purple-600 border-purple-600 text-white'
                                                            : 'border-slate-300 bg-white'
                                                    }`}
                                                >
                                                    {isChecked && <Check className="w-3 h-3 stroke-[3]" />}
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        {/* Total Count Badge */}
                        <div className="text-xs font-medium text-slate-600 bg-white border border-slate-200 px-3 py-2.5 rounded-xl shadow-sm">
                            Total: <strong className="text-purple-700 font-semibold">{items.total}</strong> items
                        </div>
                    </div>
                </div>

                {/* Table Container (Light Mode with Table Striping) */}
                <div className="rounded-2xl border border-slate-200 bg-white shadow-md overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-700">
                            <thead className="bg-slate-50 text-xs uppercase tracking-wider text-slate-600 border-b border-slate-200 font-semibold">
                                <tr>
                                    {visibleColumns.lot_serial_number && <th className="px-5 py-4">Lot/Serial</th>}
                                    {visibleColumns.barcode && <th className="px-5 py-4">Barcode</th>}
                                    {visibleColumns.product && <th className="px-5 py-4">Product</th>}
                                    {visibleColumns.branch_name && <th className="px-5 py-4">Branch</th>}
                                    {visibleColumns.product_subgroup_name && <th className="px-5 py-4">Subgroup</th>}
                                    {visibleColumns.weight && <th className="px-5 py-4 text-right">Weight (g)</th>}
                                    {visibleColumns.gold_weight && <th className="px-5 py-4 text-right">Gold Wt (g)</th>}
                                    {visibleColumns.dg_weight && <th className="px-5 py-4 text-right">D/G WT</th>}
                                    {visibleColumns.addition_wastage && <th className="px-5 py-4 text-right">Add Wastage</th>}
                                    {visibleColumns.labor_fee && <th className="px-5 py-4 text-right">Labour Fee</th>}
                                    {visibleColumns.total_wastage && <th className="px-5 py-4 text-right">Total Wastage</th>}
                                    {visibleColumns.promotion_price && <th className="px-5 py-4 text-right">Promotion Price (MMK)</th>}
                                    {visibleColumns.internal_reference && <th className="px-5 py-4">Internal Ref</th>}
                                    {visibleColumns.actions && <th className="px-5 py-4 text-center">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {items.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={14} className="px-5 py-16 text-center text-slate-400">
                                            <FileSpreadsheet className="w-10 h-10 mx-auto mb-3 text-slate-300 stroke-[1.5]" />
                                            <p className="text-base font-medium text-slate-600">No promotion items found</p>
                                            <p className="text-xs text-slate-400 mt-1">
                                                {search ? 'Try adjusting your search criteria.' : 'Import an Excel file to get started.'}
                                            </p>
                                        </td>
                                    </tr>
                                ) : (
                                    items.data.map((item, index) => {
                                        const isSold = !!item.sold_item;
                                        const soldPrice = isSold && item.sold_item?.promotion_price !== undefined && item.sold_item?.promotion_price !== null
                                            ? parseFloat(item.sold_item.promotion_price)
                                            : null;
                                        const calculatedPrice = calculatePromotionPrice(item.total_wastage, item.labor_fee);
                                        const displayPrice = isSold && soldPrice !== null ? soldPrice : calculatedPrice;
                                        const inCart = isInCart(item.id);

                                        // Table striped background (even: white, odd: slate-50/70)
                                        const stripeClass = isSold 
                                            ? 'bg-slate-100/90 text-slate-400 opacity-60' 
                                            : index % 2 === 1 
                                                ? 'bg-slate-50/70 hover:bg-purple-50/50' 
                                                : 'bg-white hover:bg-purple-50/50';

                                        return (
                                        <tr 
                                            key={item.id} 
                                            className={`transition-colors ${stripeClass}`}
                                        >
                                            {/* 1. Lot / Serial */}
                                            {visibleColumns.lot_serial_number && (
                                                <td className="px-5 py-3.5">
                                                    {item.lot_serial_number ? (
                                                        <span className={`inline-block px-2.5 py-0.5 rounded-md text-xs font-mono font-medium ${
                                                            isSold 
                                                                ? 'bg-slate-200 text-slate-500 border border-slate-300' 
                                                                : 'bg-purple-50 border border-purple-200 text-purple-700'
                                                        }`}>
                                                            {item.lot_serial_number}
                                                        </span>
                                                    ) : '-'}
                                                </td>
                                            )}

                                            {/* 2. Barcode */}
                                            {visibleColumns.barcode && (
                                                <td className={`px-5 py-3.5 font-mono font-medium ${isSold ? 'text-slate-400 line-through' : 'text-purple-700'}`}>
                                                    {item.barcode || '-'}
                                                </td>
                                            )}

                                            {/* 3. Product */}
                                            {visibleColumns.product && (
                                                <td className={`px-5 py-3.5 font-medium ${isSold ? 'text-slate-400' : 'text-slate-900'}`}>
                                                    {item.product || '-'}
                                                </td>
                                            )}

                                            {/* Branch */}
                                            {visibleColumns.branch_name && (
                                                <td className="px-5 py-3.5 text-xs text-slate-600">
                                                    {item.branch ? (
                                                        <span className="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-slate-700 font-medium">
                                                            {item.branch.name}
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-400">-</span>
                                                    )}
                                                </td>
                                            )}

                                            {/* 4. Subgroup (Default Hide) */}
                                            {visibleColumns.product_subgroup_name && (
                                                <td className="px-5 py-3.5 text-xs text-slate-500">
                                                    {item.product_subgroup_name || '-'}
                                                </td>
                                            )}

                                            {/* 5. Weight(g) (Default Hide) */}
                                            {visibleColumns.weight && (
                                                <td className="px-5 py-3.5 text-right font-mono text-slate-600">
                                                    {formatNumber(item.weight)}
                                                </td>
                                            )}

                                            {/* 6. Gold Weight (g) */}
                                            {visibleColumns.gold_weight && (
                                                <td className={`px-5 py-3.5 text-right font-mono font-medium ${isSold ? 'text-slate-400' : 'text-amber-700'}`}>
                                                    {formatNumber(item.gold_weight)}
                                                </td>
                                            )}

                                            {/* 7. D/G WT (Default Hide) */}
                                            {visibleColumns.dg_weight && (
                                                <td className="px-5 py-3.5 text-right font-mono text-slate-600">
                                                    {formatNumber(item.dg_weight)}
                                                </td>
                                            )}

                                            {/* 8. Add Wastage */}
                                            {visibleColumns.addition_wastage && (
                                                <td className="px-5 py-3.5 text-right font-mono text-slate-700 font-medium">
                                                    {formatNumber(item.addition_wastage)}
                                                </td>
                                            )}

                                            {/* 9. Labour Fee */}
                                            {visibleColumns.labor_fee && (
                                                <td className={`px-5 py-3.5 text-right font-mono ${isSold ? 'text-slate-400' : 'text-emerald-700 font-medium'}`}>
                                                    {formatNumber(item.labor_fee, 2)}
                                                </td>
                                            )}

                                            {/* 10. Total Wastage (Default Hide) */}
                                            {visibleColumns.total_wastage && (
                                                <td className="px-5 py-3.5 text-right font-mono text-slate-600">
                                                    {formatNumber(item.total_wastage)}
                                                </td>
                                            )}

                                            {/* 11. Promotion Price */}
                                            {visibleColumns.promotion_price && (
                                                <td className="px-5 py-3.5 text-right font-mono font-semibold">
                                                    {displayPrice > 0 ? (
                                                        <span className={`inline-block px-2.5 py-1 rounded-lg ${
                                                            isSold 
                                                                ? 'bg-slate-200 text-slate-700 border border-slate-300' 
                                                                : 'bg-amber-100/80 border border-amber-300 text-amber-900'
                                                        }`}
                                                        title={isSold && item.sold_item ? `Fixed Sold Price (ရောင်းဈေး): ${Math.round(displayPrice).toLocaleString()} MMK (Voucher: ${item.sold_item.reference_number || '-'})` : undefined}
                                                        >
                                                            {Math.round(displayPrice).toLocaleString()}
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-400">-</span>
                                                    )}
                                                </td>
                                            )}

                                            {/* Internal Ref (Optional) */}
                                            {visibleColumns.internal_reference && (
                                                <td className="px-5 py-3.5 font-mono text-xs text-slate-500">
                                                    {item.internal_reference || '-'}
                                                </td>
                                            )}

                                            {/* 12. Actions */}
                                            {visibleColumns.actions && (
                                                <td className="px-5 py-3.5 text-center">
                                                    {(() => {
                                                        const isBranchMatched = currentUser?.is_super_admin || 
                                                            !item.branch_id || 
                                                            !currentUser?.branch_id || 
                                                            Number(currentUser.branch_id) === Number(item.branch_id);

                                                        if (isSold) {
                                                            return (
                                                                <div className="inline-flex items-center gap-1.5">
                                                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-200/80 border border-slate-300 text-slate-600 text-xs font-medium cursor-default">
                                                                        <Tag className="w-3 h-3 text-slate-500" />
                                                                        <span>ရောင်းပြီး</span>
                                                                    </span>
                                                                    {isBranchMatched && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => handleOpenCancelModal(item)}
                                                                            title={`Cancel sold sale (${item.sold_item.reference_number})`}
                                                                            className="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 hover:text-rose-900 text-xs font-medium transition"
                                                                        >
                                                                            <Trash2 className="w-3.5 h-3.5" />
                                                                            <span>ဖျက်မည်</span>
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            );
                                                        }

                                                        if (!isBranchMatched) {
                                                            return (
                                                                <span className="inline-flex items-center px-2 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-400 text-xs font-medium">
                                                                    Other Branch
                                                                </span>
                                                            );
                                                        }

                                                        return (
                                                            <div className="inline-flex items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleBookmark(item)}
                                                                    title="မှတ်မည် (Add to Cart)"
                                                                    className={`inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition ${
                                                                        inCart 
                                                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-400 hover:bg-emerald-100 font-semibold' 
                                                                            : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 hover:text-slate-900'
                                                                    }`}
                                                                >
                                                                    {inCart ? (
                                                                        <>
                                                                            <BookmarkCheck className="w-3.5 h-3.5 text-emerald-600 stroke-[2.5]" />
                                                                            <span className="text-emerald-700">မှတ်ပြီး</span>
                                                                        </>
                                                                    ) : (
                                                                        <>
                                                                            <Bookmark className="w-3.5 h-3.5 text-slate-400" />
                                                                            <span>မှတ်မည်</span>
                                                                        </>
                                                                    )}
                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDirectUse(item)}
                                                                    title="သုံးမည် (Direct Use & Open Cart)"
                                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-sm transition"
                                                                >
                                                                    <Sparkles className="w-3.5 h-3.5" />
                                                                    <span>သုံးမည်</span>
                                                                </button>
                                                            </div>
                                                        );
                                                    })()}
                                                </td>
                                            )}
                                        </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination Bar (10 items per page) */}
                    {items.links && items.links.length > 3 && (
                        <div className="px-5 py-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/80">
                            <div className="text-xs text-slate-600">
                                Showing <span className="text-slate-900 font-medium">{items.from || 0}</span> to{' '}
                                <span className="text-slate-900 font-medium">{items.to || 0}</span> of{' '}
                                <span className="text-slate-900 font-medium">{items.total}</span> results
                            </div>

                            <div className="flex items-center gap-1.5">
                                {items.links.map((link, idx) => {
                                    if (!link.url) {
                                        return (
                                            <span
                                                key={idx}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-400 border border-slate-200 bg-white cursor-not-allowed select-none"
                                            />
                                        );
                                    }

                                    return (
                                        <button
                                            key={idx}
                                            type="button"
                                            onClick={() => router.get(link.url, {}, { preserveState: true })}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                                                link.active
                                                    ? 'bg-purple-600 text-white shadow-sm'
                                                    : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 hover:text-slate-900'
                                            }`}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </main>

            {/* Cart Modal */}
            {isCartModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
                    <div className="w-full max-w-2xl rounded-3xl bg-white border border-slate-200 p-6 shadow-2xl space-y-6">
                        <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div className="flex items-center gap-3">
                                <span className="p-2.5 rounded-xl bg-purple-100 text-purple-700 border border-purple-200">
                                    <ShoppingCart className="w-5 h-5" />
                                </span>
                                <div>
                                    <h2 className="text-lg font-bold text-slate-900">Promotion Items Cart / အရောင်းဘောက်ချာ</h2>
                                    <p className="text-xs text-slate-500">
                                        Selected items: {cartItems.length}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={() => setIsCartModalOpen(false)}
                                className="text-slate-400 hover:text-slate-600 transition p-1"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {voucherError && (
                            <div className="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
                                <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                                <span>{voucherError}</span>
                            </div>
                        )}

                        {cartItems.length === 0 ? (
                            <div className="py-12 text-center text-slate-400">
                                <ShoppingCart className="w-12 h-12 mx-auto mb-2 text-slate-300 stroke-[1.5]" />
                                <p className="text-base font-medium text-slate-600">Cart is empty</p>
                                <p className="text-xs text-slate-400 mt-1">Click "မှတ်မည်" or "သုံးမည်" on promotion items to add here.</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {/* Items Table in Modal */}
                                <div className="max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/50">
                                    <table className="w-full text-left text-xs text-slate-700">
                                        <thead className="bg-slate-100 uppercase tracking-wider text-slate-600 sticky top-0 border-b border-slate-200 font-semibold">
                                            <tr>
                                                <th className="px-4 py-3">Barcode</th>
                                                <th className="px-4 py-3">Product Name</th>
                                                <th className="px-4 py-3">Lot/Serial</th>
                                                <th className="px-4 py-3 text-right">Promotion Price</th>
                                                <th className="px-4 py-3 text-center">Remove</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-200/80">
                                            {dynamicCartItems.map((cartItem) => (
                                                <tr key={cartItem.id} className="hover:bg-purple-50/50 bg-white">
                                                    <td className="px-4 py-2.5 font-mono text-purple-700 font-medium">
                                                        {cartItem.barcode || '-'}
                                                    </td>
                                                    <td className="px-4 py-2.5 text-slate-900 font-medium">
                                                        {cartItem.product || '-'}
                                                    </td>
                                                    <td className="px-4 py-2.5 font-mono text-slate-500">
                                                        {cartItem.lot_serial_number || '-'}
                                                    </td>
                                                    <td className="px-4 py-2.5 text-right font-mono text-amber-800 font-semibold">
                                                        {Math.round(cartItem.live_price).toLocaleString()} MMK
                                                    </td>
                                                    <td className="px-4 py-2.5 text-center">
                                                        <button
                                                            type="button"
                                                            onClick={() => removeFromCart(cartItem.id)}
                                                            className="text-slate-400 hover:text-rose-600 p-1"
                                                            title="Remove item"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Grand Total Card */}
                                <div className="p-4 rounded-2xl bg-gradient-to-r from-purple-50 to-amber-50/50 border border-purple-200 flex items-center justify-between">
                                    <div>
                                        <span className="text-xs uppercase tracking-wider text-purple-800 font-semibold">
                                            Grand Total / စုစုပေါင်းတန်ဖိုး
                                        </span>
                                        <p className="text-xs text-slate-500 mt-0.5">Calculated promotion value</p>
                                    </div>
                                    <div className="text-right">
                                        <span className="text-2xl font-extrabold text-amber-700 font-mono">
                                            {roundDownToTens(grandTotal).toLocaleString()}
                                        </span>
                                        <span className="text-xs text-slate-500 ml-1.5">MMK</span>
                                    </div>
                                </div>

                                {/* Voucher Number Input Form */}
                                <form onSubmit={handleSaveAndCopy} className="space-y-4 pt-2">
                                    <div>
                                        <label className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                                            Sale Voucher Number <span className="text-rose-500">*</span> (ဘောက်ချာနံပါတ်)
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            value={voucherNumber}
                                            onChange={(e) => {
                                                setVoucherNumber(e.target.value);
                                                setVoucherError('');
                                            }}
                                            placeholder="Enter voucher / bill number (e.g. V-00124)..."
                                            className="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition shadow-sm"
                                        />
                                    </div>

                                    <div className="flex items-center justify-between pt-2 border-t border-slate-100">
                                        <button
                                            type="button"
                                            onClick={() => setCartItems([])}
                                            className="text-xs text-slate-500 hover:text-rose-600 transition"
                                        >
                                            Clear All
                                        </button>

                                        <div className="flex items-center gap-3">
                                            <button
                                                type="button"
                                                onClick={() => setIsCartModalOpen(false)}
                                                className="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-sm font-medium text-slate-700 transition"
                                            >
                                                Cancel
                                            </button>

                                            <button
                                                type="submit"
                                                disabled={isSavingSold || cartItems.length === 0}
                                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-semibold shadow-md shadow-purple-600/20 transition"
                                            >
                                                {isSavingSold ? (
                                                    <>
                                                        <RefreshCw className="w-4 h-4 animate-spin" />
                                                        <span>Processing...</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <Copy className="w-4 h-4" />
                                                        <span>Save & Copy (သိမ်းဆည်း & ကူးမည်)</span>
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Excel Upload Modal */}
            {isUploadModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
                    <div className="w-full max-w-lg rounded-3xl bg-white border border-slate-200 p-6 shadow-2xl space-y-6">
                        <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div className="flex items-center gap-3">
                                <span className="p-2 rounded-xl bg-purple-100 text-purple-700 border border-purple-200">
                                    <Upload className="w-5 h-5" />
                                </span>
                                <div>
                                    <h2 className="text-lg font-bold text-slate-900">Import Promotion Items</h2>
                                    <p className="text-xs text-slate-500">Upload Excel (.xlsx) or CSV file</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={() => {
                                    if (!isUploading) setIsUploadModalOpen(false);
                                }}
                                className="text-slate-400 hover:text-slate-600 transition p-1"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {uploadError && (
                            <div className="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
                                <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                                <span>{uploadError}</span>
                            </div>
                        )}

                        <form onSubmit={handleUploadSubmit} className="space-y-5">
                            <div className="border-2 border-dashed border-slate-300 hover:border-purple-500 rounded-2xl p-6 text-center transition cursor-pointer bg-slate-50 relative">
                                <input
                                    type="file"
                                    accept=".xlsx,.csv"
                                    disabled={isUploading}
                                    onChange={(e) => {
                                        if (e.target.files && e.target.files[0]) {
                                            setUploadFile(e.target.files[0]);
                                            setUploadError('');
                                        }
                                    }}
                                    className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                />
                                <FileSpreadsheet className="w-12 h-12 mx-auto text-purple-600 mb-2 stroke-[1.5]" />
                                {uploadFile ? (
                                    <div>
                                        <p className="text-sm font-semibold text-slate-900">{uploadFile.name}</p>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            {(uploadFile.size / 1024).toFixed(1)} KB
                                        </p>
                                    </div>
                                ) : (
                                    <div>
                                        <p className="text-sm font-medium text-slate-700">Click to browse or drag & drop</p>
                                        <p className="text-xs text-slate-500 mt-1">Supports Excel (.xlsx) and CSV files</p>
                                    </div>
                                )}
                            </div>

                            <div className="rounded-xl bg-slate-50 border border-slate-200 p-3.5 text-xs text-slate-600 space-y-1">
                                <p className="font-semibold text-slate-800">Automatic Handling:</p>
                                <ul className="list-disc list-inside space-y-0.5 text-slate-600">
                                    <li>Grouping banner rows (e.g. <code>B1/Promotion (3)</code>) are automatically ignored.</li>
                                    <li>Data columns: Barcode, Product, Branch, Weight, Wastage, Labor Fee, Lot/Serial Number.</li>
                                </ul>
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    disabled={isUploading}
                                    onClick={() => setIsUploadModalOpen(false)}
                                    className="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-sm font-medium text-slate-700 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={isUploading || !uploadFile}
                                    className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-semibold shadow-md shadow-purple-600/20 transition"
                                >
                                    {isUploading ? (
                                        <>
                                            <RefreshCw className="w-4 h-4 animate-spin" />
                                            <span>Importing...</span>
                                        </>
                                    ) : (
                                        <>
                                            <Upload className="w-4 h-4" />
                                            <span>Start Import</span>
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* VR Search Modal */}
            {isVrSearchModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
                    <div className="w-full max-w-3xl rounded-3xl bg-white border border-slate-200 p-6 shadow-2xl space-y-5 max-h-[90vh] flex flex-col">
                        {/* Modal Header */}
                        <div className="flex items-center justify-between border-b border-slate-100 pb-4 shrink-0">
                            <div className="flex items-center gap-3">
                                <span className="p-2.5 rounded-xl bg-purple-100 text-purple-700 border border-purple-200">
                                    <Receipt className="w-5 h-5" />
                                </span>
                                <div>
                                    <h2 className="text-lg font-bold text-slate-900">VR Lookup / ဘောက်ချာဖြင့် ရှာဖွေခြင်း</h2>
                                    <p className="text-xs text-slate-500">
                                        Look up sold promotion items and historical sale prices by invoice or voucher number.
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={() => setIsVrSearchModalOpen(false)}
                                className="text-slate-400 hover:text-slate-600 transition p-1"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Search Input Form */}
                        <form onSubmit={handleSearchVr} className="shrink-0 flex items-center gap-2">
                            <div className="relative flex-1">
                                <div className="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none flex items-center justify-center">
                                    <Search className="w-4 h-4 text-slate-400" />
                                </div>
                                <input
                                    type="text"
                                    value={vrSearchNumber}
                                    onChange={(e) => setVrSearchNumber(e.target.value)}
                                    placeholder="Enter invoice / voucher number (e.g., V001)..."
                                    className="w-full pl-10 pr-10 py-2.5 rounded-xl bg-white border border-slate-300 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition leading-normal font-mono"
                                    autoFocus
                                />
                                {vrSearchNumber && (
                                    <div className="absolute right-2.5 top-1/2 -translate-y-1/2 flex items-center justify-center">
                                        <button
                                            type="button"
                                            onClick={() => setVrSearchNumber('')}
                                            className="text-slate-400 hover:text-slate-600 p-1 rounded-md transition"
                                        >
                                            <X className="w-4 h-4" />
                                        </button>
                                    </div>
                                )}
                            </div>
                            <button
                                type="submit"
                                disabled={isSearchingVr}
                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white text-sm font-semibold shadow-md shadow-purple-600/20 transition shrink-0"
                            >
                                {isSearchingVr ? (
                                    <>
                                        <RefreshCw className="w-4 h-4 animate-spin" />
                                        <span>Searching...</span>
                                    </>
                                ) : (
                                    <>
                                        <Search className="w-4 h-4" />
                                        <span>Search / ရှာမည်</span>
                                    </>
                                )}
                            </button>
                        </form>

                        {/* Error Notice */}
                        {vrSearchError && (
                            <div className="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2 shrink-0">
                                <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                                <span>{vrSearchError}</span>
                            </div>
                        )}

                        {/* Modal Body / Results Area */}
                        <div className="flex-1 overflow-y-auto space-y-4 min-h-[160px] pr-1">
                            {!vrSearchResult && !isSearchingVr && !vrSearchError && (
                                <div className="text-center py-12 text-slate-400 space-y-2">
                                    <Receipt className="w-10 h-10 mx-auto text-slate-300 stroke-[1.5]" />
                                    <p className="text-sm font-medium text-slate-500">
                                        Enter a voucher or invoice number above to view sold items and amounts.
                                    </p>
                                    <p className="text-xs text-slate-400">
                                        (အရောင်းဘောက်ချာနံပါတ်ရိုက်ထည့်ပြီး ရှာဖွေပါ)
                                    </p>
                                </div>
                            )}

                            {isSearchingVr && (
                                <div className="text-center py-12 text-slate-400 space-y-3">
                                    <RefreshCw className="w-8 h-8 mx-auto text-purple-600 animate-spin" />
                                    <p className="text-sm font-medium text-slate-600">Searching voucher records...</p>
                                </div>
                            )}

                            {vrSearchResult && vrSearchResult.items && vrSearchResult.items.length === 0 && (
                                <div className="text-center py-12 bg-amber-50/50 rounded-2xl border border-amber-200 space-y-2">
                                    <AlertCircle className="w-8 h-8 mx-auto text-amber-500" />
                                    <p className="text-sm font-semibold text-amber-950">
                                        No items found for voucher "{vrSearchResult.reference_number}"
                                    </p>
                                    <p className="text-xs text-amber-800">
                                        ဤဘောက်ချာနံပါတ်ဖြင့် ရောင်းချထားသော ပရိုမိုးရှင်းပစ္စည်း ရှာမတွေ့ပါ။
                                    </p>
                                </div>
                            )}

                            {vrSearchResult && vrSearchResult.items && vrSearchResult.items.length > 0 && (() => {
                                const vrGrandTotal = vrSearchResult.items.reduce(
                                    (sum, item) => sum + (parseFloat(item.promotion_price) || 0),
                                    0
                                );
                                const firstItem = vrSearchResult.items[0];
                                const formattedSaleDate = formatRelativeDateTime(firstItem.created_at);

                                return (
                                    <div className="space-y-4">
                                        {/* Summary Header Info Bar */}
                                        <div className="p-3.5 rounded-2xl bg-purple-50/70 border border-purple-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                                            <div className="flex items-center gap-3">
                                                <div>
                                                    <span className="text-slate-500 block text-[10px] uppercase font-semibold">Voucher / Invoice</span>
                                                    <span className="font-mono font-bold text-purple-900 text-sm">
                                                        {vrSearchResult.reference_number}
                                                    </span>
                                                </div>
                                                {firstItem.user?.name && (
                                                    <div className="border-l border-purple-200 pl-3">
                                                        <span className="text-slate-500 block text-[10px] uppercase font-semibold">Sold By</span>
                                                        <span className="font-medium text-slate-800">{firstItem.user.name}</span>
                                                    </div>
                                                )}
                                                {firstItem.promotion_item?.branch?.name && (
                                                    <div className="border-l border-purple-200 pl-3">
                                                        <span className="text-slate-500 block text-[10px] uppercase font-semibold">Branch</span>
                                                        <span className="font-medium text-slate-800">{firstItem.promotion_item.branch.name}</span>
                                                    </div>
                                                )}
                                            </div>

                                            <div className="text-right">
                                                <span className="text-slate-500 block text-[10px] uppercase font-semibold">Sale Date & Time</span>
                                                <span className="font-medium text-slate-700">{formattedSaleDate || '-'}</span>
                                            </div>
                                        </div>

                                        {/* Items Table */}
                                        <div className="rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                                            <table className="w-full text-left text-xs text-slate-700">
                                                <thead className="bg-slate-100 uppercase tracking-wider text-slate-600 border-b border-slate-200 font-semibold text-[11px]">
                                                    <tr>
                                                        <th className="px-3.5 py-2.5 text-center w-12">#</th>
                                                        <th className="px-3.5 py-2.5">Barcode</th>
                                                        <th className="px-3.5 py-2.5">Product</th>
                                                        <th className="px-3.5 py-2.5">Lot / Serial</th>
                                                        <th className="px-3.5 py-2.5 text-right">Gold Wt (g)</th>
                                                        <th className="px-3.5 py-2.5 text-right">Sold Promo Price</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100">
                                                    {vrSearchResult.items.map((sold, idx) => {
                                                        const promo = sold.promotion_item || {};
                                                        return (
                                                            <tr key={sold.id} className={idx % 2 === 1 ? 'bg-slate-50/60' : 'bg-white'}>
                                                                <td className="px-3.5 py-2.5 text-center text-slate-400 font-mono">
                                                                    {idx + 1}
                                                                </td>
                                                                <td className="px-3.5 py-2.5 font-mono text-purple-700 font-semibold">
                                                                    {promo.barcode || '-'}
                                                                </td>
                                                                <td className="px-3.5 py-2.5 font-medium text-slate-900">
                                                                    {promo.product || '-'}
                                                                </td>
                                                                <td className="px-3.5 py-2.5 font-mono text-slate-600">
                                                                    {promo.lot_serial_number || '-'}
                                                                </td>
                                                                <td className="px-3.5 py-2.5 text-right font-mono text-slate-700">
                                                                    {formatNumber(promo.gold_weight, 2)}
                                                                </td>
                                                                <td className="px-3.5 py-2.5 text-right font-mono font-bold text-amber-900">
                                                                    {Math.round(sold.promotion_price).toLocaleString()} MMK
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </div>

                                        {/* Grand Total Bar with Copy Button */}
                                        <div className="p-4 rounded-2xl bg-amber-50/80 border border-amber-300 flex items-center justify-between gap-4 shadow-sm">
                                            <div className="flex items-center gap-3">
                                                <span className="p-2 rounded-xl bg-amber-200/70 text-amber-900">
                                                    <Tag className="w-5 h-5" />
                                                </span>
                                                <div>
                                                    <span className="text-xs uppercase tracking-wider font-semibold text-amber-900 block">
                                                        Grand Total (စုစုပေါင်းကျသင့်ငွေ)
                                                    </span>
                                                    <div className="text-xl font-mono font-bold text-amber-950">
                                                        {Math.round(vrGrandTotal).toLocaleString()} <span className="text-sm font-normal text-amber-800">MMK</span>
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Copy Button */}
                                            <button
                                                type="button"
                                                onClick={() => handleCopyVrGrandTotal(Math.round(vrGrandTotal))}
                                                className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-xs transition shadow-sm ${
                                                    vrCopiedSuccess
                                                        ? 'bg-emerald-600 text-white shadow-emerald-600/20'
                                                        : 'bg-white hover:bg-amber-100 text-amber-900 border border-amber-300'
                                                }`}
                                            >
                                                {vrCopiedSuccess ? (
                                                    <>
                                                        <Check className="w-4 h-4 text-white" />
                                                        <span>Copied Total! (ကူးယူပြီး)</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <Copy className="w-4 h-4 text-amber-700" />
                                                        <span>Copy Total (ကူးမည်)</span>
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                );
                            })()}
                        </div>

                        {/* Modal Footer */}
                        <div className="flex items-center justify-between pt-3 border-t border-slate-100 shrink-0">
                            <div>
                                {vrSearchResult && vrSearchResult.items && vrSearchResult.items.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={handleTriggerCancelFromVr}
                                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 text-xs font-semibold shadow-sm transition"
                                        title="Cancel / void sold items under this voucher"
                                    >
                                        <RotateCcw className="w-4 h-4 text-rose-600" />
                                        <span>ဖျက်သိမ်းရန် (Cancel Voucher Sale)</span>
                                    </button>
                                )}
                            </div>

                            <button
                                type="button"
                                onClick={() => setIsVrSearchModalOpen(false)}
                                className="px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-xs font-semibold text-slate-700 transition"
                            >
                                Close (ပိတ်မည်)
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Cancel Sold Items Modal */}
            {isCancelModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
                    <div className="w-full max-w-2xl rounded-3xl bg-white border border-slate-200 p-6 shadow-2xl space-y-6">
                        <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div className="flex items-center gap-3">
                                <span className="p-2.5 rounded-xl bg-rose-100 text-rose-700 border border-rose-200">
                                    <Trash2 className="w-5 h-5" />
                                </span>
                                <div>
                                    <h2 className="text-lg font-bold text-slate-900">Cancel Sold Items / အရောင်းဖျက်သိမ်းခြင်း</h2>
                                    <p className="text-xs text-slate-500">
                                        Voucher Number: <span className="font-mono text-purple-700 font-semibold">{cancelVoucherNumber}</span>
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={() => {
                                    if (!isSubmittingCancel) setIsCancelModalOpen(false);
                                }}
                                className="text-slate-400 hover:text-slate-600 transition p-1"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {cancelError && (
                            <div className="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
                                <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                                <span>{cancelError}</span>
                            </div>
                        )}

                        {isLoadingVoucherItems ? (
                            <div className="py-12 flex flex-col items-center justify-center gap-3 text-slate-400">
                                <RefreshCw className="w-8 h-8 animate-spin text-purple-600" />
                                <span className="text-sm">Loading voucher items...</span>
                            </div>
                        ) : (
                            <form onSubmit={handleCancelSubmit} className="space-y-5">
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <label className="text-xs font-semibold uppercase tracking-wider text-slate-700">
                                            Voucher Items ({cancelVoucherItems.length}) - Select items to cancel:
                                        </label>
                                        <div className="text-xs text-slate-500">
                                            Selected: <span className="text-rose-600 font-semibold">{selectedCancelItemIds.length}</span> of {cancelVoucherItems.length}
                                        </div>
                                    </div>

                                    {/* Items List for the same Voucher Number */}
                                    <div className="max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/50 divide-y divide-slate-200">
                                        {cancelVoucherItems.map((sold) => {
                                            const isSelected = selectedCancelItemIds.includes(sold.id);
                                            const promo = sold.promotion_item || {};

                                            return (
                                                <div 
                                                    key={sold.id}
                                                    onClick={() => toggleCancelItemSelection(sold.id)}
                                                    className={`p-3.5 flex items-center justify-between gap-3 cursor-pointer transition ${
                                                        isSelected ? 'bg-rose-50' : 'hover:bg-slate-100 bg-white'
                                                    }`}
                                                >
                                                    <div className="flex items-center gap-3 min-w-0">
                                                        <div className={`w-4 h-4 rounded border flex items-center justify-center shrink-0 ${
                                                            isSelected ? 'bg-rose-600 border-rose-600 text-white' : 'border-slate-300 bg-white'
                                                        }`}>
                                                            {isSelected && <Check className="w-3 h-3 stroke-[3]" />}
                                                        </div>
                                                        <div className="min-w-0">
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-mono text-xs text-purple-700 font-semibold">{promo.barcode || '-'}</span>
                                                                <span className="text-xs text-slate-500 font-mono">({promo.lot_serial_number || '-'})</span>
                                                            </div>
                                                            <p className="text-xs text-slate-900 truncate mt-0.5">{promo.product || '-'}</p>
                                                        </div>
                                                    </div>

                                                    <div className="text-right shrink-0">
                                                        <span className="font-mono text-xs font-semibold text-amber-800">
                                                            {Number(sold.promotion_price).toLocaleString()} MMK
                                                        </span>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                    <p className="text-xs text-slate-500 mt-1.5">
                                        Tip: Click on items above to unselect or select which products to cancel from this voucher.
                                    </p>
                                </div>

                                {/* Reason Input */}
                                <div>
                                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                                        Cancellation Reason <span className="text-rose-500">*</span> (ဖျက်သိမ်းရသည့် အကြောင်းပြချက်)
                                    </label>
                                    <textarea
                                        required
                                        rows={3}
                                        value={cancelReason}
                                        onChange={(e) => {
                                            setCancelReason(e.target.value);
                                            setCancelError('');
                                        }}
                                        placeholder="Enter reason why this sold item is being cancelled (e.g., Customer return, wrong voucher input)..."
                                        className="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition shadow-sm"
                                    />
                                </div>

                                <div className="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                                    <button
                                        type="button"
                                        disabled={isSubmittingCancel}
                                        onClick={() => setIsCancelModalOpen(false)}
                                        className="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-sm font-medium text-slate-700 transition"
                                    >
                                        Back
                                    </button>

                                    <button
                                        type="submit"
                                        disabled={isSubmittingCancel || selectedCancelItemIds.length === 0}
                                        className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white text-sm font-semibold shadow-md shadow-rose-600/20 transition"
                                    >
                                        {isSubmittingCancel ? (
                                            <>
                                                <RefreshCw className="w-4 h-4 animate-spin" />
                                                <span>Canceling...</span>
                                            </>
                                        ) : (
                                            <>
                                                <Trash2 className="w-4 h-4" />
                                                <span>Confirm Cancel ({selectedCancelItemIds.length} items)</span>
                                            </>
                                        )}
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            )}

            {/* Import Errors Modal */}
            {isImportErrorsModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
                    <div className="w-full max-w-3xl rounded-3xl bg-white border border-slate-200 p-6 shadow-2xl space-y-5">
                        <div className="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div className="flex items-center gap-3">
                                <span className="p-2.5 rounded-xl bg-rose-100 text-rose-700 border border-rose-200">
                                    <AlertCircle className="w-6 h-6" />
                                </span>
                                <div>
                                    <h2 className="text-lg font-bold text-slate-900">Import Failed - Error Log</h2>
                                    <p className="text-xs text-rose-600 font-medium">
                                        Import has been stopped due to validation errors (duplicate barcode/lot numbers or branch issues).
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={() => setIsImportErrorsModalOpen(false)}
                                className="text-slate-400 hover:text-slate-600 transition p-1"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <div className="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-900 leading-relaxed">
                            <p className="font-semibold mb-1 text-rose-950">
                                ⚠️ No data was imported to prevent inconsistent or duplicate records.
                            </p>
                            <p className="text-rose-800">
                                Please resolve duplicate Lot/Serial numbers, duplicate Barcodes, or branch name mismatches in your file and try importing again.
                            </p>
                        </div>

                        <div className="max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/50">
                            <table className="w-full text-left text-xs text-slate-700">
                                <thead className="bg-slate-100 uppercase tracking-wider text-slate-600 border-b border-slate-200 sticky top-0 font-semibold">
                                    <tr>
                                        <th className="px-4 py-3 text-center w-16">Row</th>
                                        <th className="px-4 py-3">Barcode</th>
                                        <th className="px-4 py-3">Product</th>
                                        <th className="px-4 py-3">Error Details</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200">
                                    {currentImportErrors.map((err, idx) => (
                                        <tr key={idx} className="hover:bg-purple-50/40 bg-white">
                                            <td className="px-4 py-2.5 font-mono text-center font-bold text-rose-600">
                                                {err.row || idx + 1}
                                            </td>
                                            <td className="px-4 py-2.5 font-mono text-purple-700 font-medium">
                                                {err.barcode || '-'}
                                            </td>
                                            <td className="px-4 py-2.5 text-slate-900">
                                                {err.product || '-'}
                                            </td>
                                            <td className="px-4 py-2.5 text-rose-700">
                                                {err.message || 'Branch name does not match any branch.'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                onClick={() => setIsImportErrorsModalOpen(false)}
                                className="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold shadow-sm transition"
                            >
                                Close & Fix File
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
