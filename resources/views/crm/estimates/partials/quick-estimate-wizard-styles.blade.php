<style>
    #quickEstimateModal .modal-dialog {
        z-index: 1055;
    }

    #addCustomerModal,
    #quickAddBomModal {
        z-index: 1065 !important;
    }

    body.modal-open .modal-backdrop.show ~ .modal-backdrop.show {
        z-index: 1060;
    }

    #quickEstimateModal .quick-bom-row .quick-bom-select-col {
        min-width: 0;
    }

    .select2-dropdown,
    .select2-container--open {
        z-index: 99999 !important;
    }

    #quickEstimateModal .d-flex:has(.is-invalid) ~ .invalid-feedback {
        display: block !important;
    }

    @media (min-width: 768px) {
        #quickEstimateModal .quick-comment-col,
        #quickEstimateModal .quick-totals-col {
            display: flex;
            flex-direction: column;
        }

        #quickEstimateModal .quick-comment-col textarea {
            flex: 1;
            min-height: 210px;
        }

        #quickEstimateModal .quick-totals-card {
            height: 100%;
        }
    }

    @media (max-width: 767.98px) {
        #addCustomerModal.modal,
        #quickAddBomModal.modal {
            padding-left: 1.25rem !important;
            padding-right: 1.25rem !important;
        }

        #addCustomerModal .modal-dialog,
        #quickAddBomModal .modal-dialog,
        .quick-estimate-nested-modal {
            margin: 1.5rem auto !important;
            max-width: min(340px, calc(100vw - 2.5rem)) !important;
            width: 100% !important;
        }

        #addCustomerModal .modal-body,
        #quickAddBomModal .modal-body {
            padding: 1rem;
        }
    }

    @media (max-width: 767.98px) {
        #quickEstimateModal {
            padding-bottom: 220px !important;
        }

        #quickEstimateModal .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
            align-items: flex-start;
            min-height: calc(100% - 1rem);
        }

        #quickEstimateModal .modal-content {
            margin-bottom: 3rem;
            overflow-x: clip;
        }

        #quickEstimateModal .modal-body {
            padding-bottom: 3rem !important;
            padding-left: 0.75rem !important;
            padding-right: 0.75rem !important;
            overflow-x: clip;
        }

        #quickEstimateModal .quick-totals-card .totals-row > .input-small {
            width: 120px;
            min-width: 120px;
            max-width: 160px;
        }

        #quickEstimateModal .quick-step-1,
        #quickEstimateModal .quick-step-2,
        #quickEstimateModal .quick-step-3 {
            display: none !important;
        }

        #quickEstimateModal .active-step {
            display: block !important;
        }

        #quickEstimateModal .active-step > .row {
            display: flex !important;
            flex-wrap: wrap;
        }

        .quick-bom-row-grid .quick-bom-select-col,
        .quick-bom-row-grid .quick-bom-make-col {
            grid-column: span 2;
        }

        .quick-step-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .quick-step-dot {
            height: 8px;
            width: 100%;
            background: #e9ecef;
            border-radius: 10px;
            transition: 0.3s;
        }

        .quick-step-dot.active {
            background: #121a33;
        }
    }

    @media (min-width: 768px) {
        .quick-step-indicator {
            display: none !important;
        }
    }
</style>
