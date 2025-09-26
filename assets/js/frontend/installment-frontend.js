/**
 * Installment Orders Accordion JavaScript
 * 
 * Handles accordion functionality for installment orders list
 */

document.addEventListener("DOMContentLoaded", function() {
    const accordionButtons = document.querySelectorAll(".view-installment-status-btn");
    
    accordionButtons.forEach(function(button) {
        button.addEventListener("click", function() {
            const orderId = this.getAttribute("data-order-id");
            const content = document.getElementById("installment-status-" + orderId);
            const icon = this.querySelector(".btn-icon svg");
            const text = this.querySelector(".btn-text");
            
            if (content.style.display === "none" || content.style.display === "") {
                // Open accordion
                content.style.display = "block";
                content.style.maxHeight = content.scrollHeight + "px";
                icon.style.transform = "rotate(180deg)";
                this.classList.add("active");
            } else {
                // Close accordion
                content.style.maxHeight = "0px";
                setTimeout(function() {
                    content.style.display = "none";
                }, 300);
                icon.style.transform = "rotate(0deg)";
                this.classList.remove("active");
            }
        });
    });
});
