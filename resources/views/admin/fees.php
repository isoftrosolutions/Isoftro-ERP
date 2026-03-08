<?php
/**
 * Admin Fee Views
 * This file handles the main template for fee management
 */
?>

<div id="feeModuleContainer">
    <!-- Content is dynamically rendered via ia-fees.js -->
    <div class="pg-loading">
        <i class="fa-solid fa-circle-notch fa-spin"></i>
        <span>Loading Fee Module...</span>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Default to setup view if no sub-action
        const path = window.location.hash || '';
        if (path.includes('fees/setup')) {
            renderFeeSetup();
        } else if (path.includes('fees/record')) {
            renderFeeRecord();
        } else if (path.includes('fees/outstanding')) {
            renderFeeOutstanding();
        } else if (path.includes('fees/details')) {
            renderFeeDetails();
        } else {
            renderFeeSetup();
        }
    });
</script>
