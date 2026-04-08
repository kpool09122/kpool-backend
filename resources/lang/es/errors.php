<?php

declare(strict_types=1);

return [
    'agency_not_found' => 'No se encontró el borrador del wiki de la agencia especificada.',
    'exists_approved_but_not_translated_agency' => 'Existe un borrador del wiki de la agencia aprobado que aún no ha sido traducido.',
    'disallowed' => 'Esta operación no está permitida.',
    'allow_only_under_review_status' => 'Solo se pueden aprobar los borradores del wiki con estado enviado.',
    'wiki_not_found' => 'No se encontró el borrador del wiki especificado.',
    'duplicate_slug' => 'El slug especificado ya existe.',
    'exists_approved_draft_wiki' => 'Existe un borrador del wiki aprobado que aún no ha sido publicado.',
    'internal_server_error' => 'Se ha producido un error en el servidor.',
    'payment_not_found' => 'No se encontró el pago especificado.',
    'invalid_payment_status' => 'El estado del pago no es válido para esta operación.',
    'refund_exceeds_captured_amount' => 'El monto del reembolso excede el monto capturado.',
    'refund_currency_mismatch' => 'La moneda del reembolso no coincide con la moneda del pago.',
    'payment_gateway_error' => 'Se ha producido un error en la pasarela de pago.',
    'monetization_account_not_found' => 'No se encontró la cuenta de monetización especificada.',
    'monetization_account_already_exists' => 'La cuenta de monetización ya existe.',
    'capability_already_granted' => 'La capacidad especificada ya ha sido otorgada.',
    'stripe_connect_error' => 'Se ha producido un error en Stripe Connect.',
    'empty_invoice_lines' => 'Se requiere al menos una línea de producto.',
    'invalid_invoice_amounts' => 'Los montos de la factura no son válidos.',
    'invoice_not_payable' => 'La factura no se encuentra en un estado pagable.',
    'invoice_not_found' => 'No se encontró la factura especificada.',
    'payment_order_mismatch' => 'El pago y la factura no corresponden al mismo pedido.',
    'payment_not_captured' => 'El pago debe ser capturado antes de vincularlo a una factura.',
    'payment_currency_mismatch_for_invoice' => 'La moneda del pago no coincide con la moneda de la factura.',
    'payment_amount_mismatch_for_invoice' => 'El monto del pago no coincide con el total de la factura.',
    'transfer_not_found' => 'No se encontró la transferencia especificada.',
    'settlement_schedule_not_found' => 'No se encontró el programa de liquidación especificado.',
];
