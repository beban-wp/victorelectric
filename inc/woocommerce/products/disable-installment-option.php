<?php
/**
 * Disable Installment Option
 * 
 * Disables installment payment option and shows warning modal
 * when users try to select it.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add CSS to disable installment option
 */
function beban_disable_installment_option_css() {
    if (!is_product()) {
        return;
    }
    
    $product = wc_get_product(get_the_ID());
    if (!$product || !$product->is_type('variable')) {
        return;
    }
    
    echo '<style>
    /* Disable installment option */
    .wpcvs-term[data-term="pay-deposit"] {
        opacity: 0.3 !important;
        cursor: not-allowed !important;
        pointer-events: auto !important;
        position: relative;
    }
    
    
    .wpcvs-term[data-term="pay-deposit"] .wpcvs-term-inner {
        background-color: #f5f5f5 !important;
        color: #999 !important;
        border: 2px dashed #ccc !important;
    }
    
    .wpcvs-term[data-term="pay-deposit"].wpcvs-disabled {
        pointer-events: none !important;
    }
    
    .wpcvs-term[data-term="pay-deposit"][disabled] {
        pointer-events: none !important;
        opacity: 0.3 !important;
    }
    
    /* Modal styles */
    .beban-installment-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.8);
        backdrop-filter: blur(8px);
    }
    
    .beban-installment-modal-content {
        background-color: #fff;
        margin: 15% auto;
        padding: 30px;
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .beban-installment-modal-header {
        margin-bottom: 20px;
    }
    
    .beban-installment-modal-icon {
        font-size: 48px;
        margin-bottom: 15px;
        color: #ff6b6b;
    }
    
    .beban-installment-modal-title {
        font-size: 24px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
    }
    
    .beban-installment-modal-message {
        font-size: 16px;
        color: #666;
        line-height: 1.6;
        margin-bottom: 25px;
    }
    
    .beban-installment-modal-close {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .beban-installment-modal-close:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .beban-installment-modal-content {
            margin: 20% auto;
            padding: 20px;
            width: 95%;
        }
        
        .beban-installment-modal-title {
            font-size: 20px;
        }
        
        .beban-installment-modal-message {
            font-size: 14px;
        }
    }
    </style>';
}
add_action('wp_head', 'beban_disable_installment_option_css');

/**
 * Add JavaScript to handle installment option clicks
 */
function beban_disable_installment_option_js() {
    if (!is_product()) {
        return;
    }
    
    $product = wc_get_product(get_the_ID());
    if (!$product || !$product->is_type('variable')) {
        return;
    }
     
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Create modal HTML
        const modalHTML = `
            <div id="beban-installment-modal" class="beban-installment-modal">
                <div class="beban-installment-modal-content">
                    <div class="beban-installment-modal-header">
                        <div class="beban-installment-modal-icon">⚠️</div>
                        <h3 class="beban-installment-modal-title">ثبت‌نام اقساطی غیرفعال</h3>
                    </div>
                    <div class="beban-installment-modal-message">
                        متأسفانه در حال حاضر امکان ثبت‌نام اقساطی در سایت وجود ندارد.<br>
                        لطفاً گزینه "خرید کامل" را انتخاب کنید.
                    </div>
                    <button class="beban-installment-modal-close" onclick="closeInstallmentModal()">
                        متوجه شدم
                    </button>
                </div>
            </div>
        `;
        
        // Add modal to body
        document.body.insertAdjacentHTML("beforeend", modalHTML);
        
        // Function to show modal
        function showInstallmentModal() {
            const modal = document.getElementById("beban-installment-modal");
            if (modal) {
                modal.style.display = "block";
                document.body.style.overflow = "hidden";
            }
        }
         
        // Function to close modal
        window.closeInstallmentModal = function() {
            const modal = document.getElementById("beban-installment-modal");
            if (modal) {
                modal.style.display = "none";
                document.body.style.overflow = "auto";
            }
        };
        
        // Close modal when clicking outside
        document.addEventListener("click", function(event) {
            const modal = document.getElementById("beban-installment-modal");
            if (event.target === modal) {
                closeInstallmentModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener("keydown", function(event) {
            if (event.key === "Escape") {
                closeInstallmentModal();
            }
        });
        
        // Handle installment option clicks
        function handleInstallmentClicks() {
            try {
                console.log("Beban: Setting up installment click handlers");
                
                // Handle WPCVS buttons
                const installmentButtons = document.querySelectorAll(".wpcvs-term[data-term=\"pay-deposit\"]");
                console.log("Beban: Found", installmentButtons.length, "installment buttons");
                
                installmentButtons.forEach(function(button, index) {
                    if (!button.hasAttribute("data-beban-handled")) {
                        button.setAttribute("data-beban-handled", "true");
                        button.setAttribute("disabled", "true");
                        button.classList.add("wpcvs-disabled");
                        console.log("Beban: Adding click handler to button", index);
                        
                        // Remove disabled attribute temporarily for click detection
                        button.removeAttribute("disabled");
                        button.style.pointerEvents = "auto";
                        
                        button.addEventListener("click", function(e) {
                            console.log("Beban: Installment button clicked!");
                            e.preventDefault();
                            e.stopPropagation();
                            
                            // Remove any existing selection
                            button.classList.remove("wpcvs-selected");
                            
                            // Ensure pay-full is selected
                            const payFullButton = document.querySelector(".wpcvs-term[data-term=\"pay-full\"]");
                            if (payFullButton) {
                                payFullButton.classList.add("wpcvs-selected");
                            }
                            
                            // Update select value
                            const select = document.querySelector("select[name=\"attribute_pa_type-order\"]");
                            if (select) {
                                select.value = "pay-full";
                                select.dispatchEvent(new Event("change"));
                            }
                            
                            showInstallmentModal();
                            return false;
                        });
                    }
                });
                
                // Handle select option
                const installmentSelect = document.querySelector("select[name=\"attribute_pa_type-order\"] option[value=\"pay-deposit\"]");
                if (installmentSelect && !installmentSelect.disabled) {
                    installmentSelect.disabled = true;
                    installmentSelect.style.color = "#999";
                    installmentSelect.style.backgroundColor = "#f5f5f5";
                }
                
                // Prevent form submission with installment option
                const form = document.querySelector(".variations_form");
                if (form && !form.hasAttribute("data-beban-handled")) {
                    form.setAttribute("data-beban-handled", "true");
                    form.addEventListener("submit", function(e) {
                        const selectedOption = document.querySelector("select[name=\"attribute_pa_type-order\"]").value;
                        if (selectedOption === "pay-deposit") {
                            e.preventDefault();
                            showInstallmentModal();
                            return false;
                        }
                    });
                }
            } catch (error) {
                console.log("Beban installment handler error:", error);
            }
        }
        
        // Multiple attempts to ensure it works
        setTimeout(handleInstallmentClicks, 1000);
        setTimeout(handleInstallmentClicks, 3000);
        setTimeout(handleInstallmentClicks, 5000);
    });
    </script>';
}
add_action('wp_footer', 'beban_disable_installment_option_js');
