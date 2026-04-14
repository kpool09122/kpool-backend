using TypeSpec.Http;
using KPool.Common;

@service(#{ title: "k-pool Monetization API" })
@server("/api/monetization", "Laravel route prefix from bootstrap/app.php.")
namespace KPool.MonetizationApi;

@doc("Supported currency codes used by monetization value objects.")
enum CurrencyCode {
  JPY,
  USD,
  KRW,
}

@doc("Country codes accepted by seller onboarding.")
enum SellerOnboardingCountryCode {
  JP,
  KR,
  US,
}

@doc("Capabilities granted to a monetization account.")
enum MonetizationCapability {
  purchase,
  sell,
  receive_payout,
}

@doc("Supported payment method types.")
enum PaymentMethodType {
  card,
  bank_transfer,
  wallet,
}

@doc("Account payment method type accepted by registration.")
enum AccountPaymentMethodType {
  card,
}

@doc("Account holder classification for payout accounts.")
enum AccountHolderType {
  individual,
  company,
}

@doc("Payment lifecycle status.")
enum PaymentStatus {
  pending,
  authorized,
  captured,
  partially_refunded,
  refunded,
  failed,
}

@doc("Invoice lifecycle status.")
scalar InvoiceStatus extends string;

@doc("Settlement batch status.")
enum SettlementStatus {
  pending,
  processing,
  paid,
  failed,
}

@doc("Transfer execution status.")
enum TransferStatus {
  pending,
  sent,
  failed,
}

alias EmptyJsonArray = unknown[];

@doc("Monetization account snapshot returned after provisioning.")
model MonetizationAccountSummary {
  @doc("Monetization account identifier.")
  monetizationAccountIdentifier: Uuid;
  @doc("Owning account identifier.")
  accountIdentifier: Uuid;
  @doc("Granted capabilities.")
  capabilities: MonetizationCapability[];
  @doc("Stripe customer identifier, when available.")
  stripeCustomerId?: string | null;
  @doc("Stripe connected account identifier, when available.")
  stripeConnectedAccountId?: string | null;
}

@doc("Seller onboarding URL payload.")
model SellerOnboardingSummary {
  @doc("Stripe onboarding URL.")
  onboardingUrl?: string | null;
}

@doc("Registered payment method snapshot.")
model RegisteredPaymentMethodSummary {
  @doc("Registered payment method identifier.")
  registeredPaymentMethodIdentifier: Uuid;
  @doc("Gateway payment method identifier.")
  paymentMethodId: string;
  @doc("Registered payment method type.")
  type: AccountPaymentMethodType;
  @doc("Card brand returned from metadata.")
  brand?: string | null;
  @doc("Last four digits returned from metadata.")
  last4?: string | null;
  @doc("Expiration month returned from metadata.")
  expMonth?: int32 | null;
  @doc("Expiration year returned from metadata.")
  expYear?: int32 | null;
  @doc("Whether the method is the default one.")
  isDefault: boolean;
  @doc("Whether registration was skipped because the method already existed.")
  skipped: boolean;
}

@doc("Payment snapshot returned by authorize/capture/refund commands.")
model PaymentSummary {
  @doc("Payment identifier.")
  paymentId: Uuid;
  @doc("Order identifier.")
  orderIdentifier: Uuid;
  @doc("Buyer monetization account identifier.")
  buyerMonetizationAccountIdentifier: Uuid;
  @doc("Authorized payment amount.")
  amount: int32;
  @doc("Currency for the payment amount.")
  currency: CurrencyCode;
  @doc("Payment method identifier.")
  paymentMethodIdentifier: Uuid;
  @doc("Payment method type.")
  paymentMethodType: PaymentMethodType;
  @doc("Human-readable payment method label.")
  paymentMethodLabel: string;
  @doc("Whether recurring usage is enabled.")
  paymentMethodRecurringEnabled: boolean;
  @doc("Current payment status.")
  status: PaymentStatus;
  @doc("Creation timestamp.")
  createdAt: Timestamp;
  @doc("Authorization timestamp.")
  authorizedAt?: Timestamp | null;
  @doc("Capture timestamp.")
  capturedAt?: Timestamp | null;
  @doc("Failure timestamp.")
  failedAt?: Timestamp | null;
  @doc("Failure reason when the payment failed.")
  failureReason?: string | null;
  @doc("Total refunded amount.")
  refundedAmount: int32;
  @doc("Currency for the refunded amount.")
  refundedCurrency: CurrencyCode;
  @doc("Timestamp of the latest refund.")
  lastRefundedAt?: Timestamp | null;
  @doc("Reason for the latest refund.")
  lastRefundReason?: string | null;
}

@doc("Invoice snapshot returned by create/record-payment commands.")
model InvoiceSummary {
  @doc("Invoice identifier.")
  invoiceIdentifier: Uuid;
  @doc("Order identifier.")
  orderIdentifier: Uuid;
  @doc("Buyer monetization account identifier.")
  buyerMonetizationAccountIdentifier: Uuid;
  @doc("Subtotal amount before discount and tax.")
  subtotal: int32;
  @doc("Discount amount applied to the invoice.")
  discountAmount: int32;
  @doc("Tax amount applied to the invoice.")
  taxAmount: int32;
  @doc("Total invoice amount.")
  total: int32;
  @doc("Invoice currency.")
  currency: CurrencyCode;
  @doc("Current invoice status.")
  status: InvoiceStatus;
  @doc("Issuance timestamp.")
  issuedAt: Timestamp;
  @doc("Payment due timestamp.")
  dueDate: Timestamp;
  @doc("Payment completion timestamp.")
  paidAt?: Timestamp | null;
}

@doc("Settlement batch result returned after settling revenue.")
model SettlementResultSummary {
  @doc("Settlement batch identifier.")
  settlementBatchIdentifier: Uuid;
  @doc("Beneficiary monetization account identifier.")
  monetizationAccountIdentifier: Uuid;
  @doc("Settlement currency.")
  currency: CurrencyCode;
  @doc("Gross settled amount.")
  grossAmount: int32;
  @doc("Aggregated fee amount.")
  feeAmount: int32;
  @doc("Net amount to transfer.")
  netAmount: int32;
  @doc("Settlement batch status.")
  status: SettlementStatus;
  @doc("Settlement period start timestamp.")
  periodStart: Timestamp;
  @doc("Settlement period end timestamp.")
  periodEnd: Timestamp;
  @doc("Transfer identifier created for the batch.")
  transferIdentifier: Uuid;
  @doc("Transfer status.")
  transferStatus: TransferStatus;
}

@doc("Invoice line request item.")
model InvoiceLineRequestItem {
  @doc("Line item description.")
  description: string;
  @doc("Unit price amount.")
  unitPriceAmount: int32;
  @doc("Purchased quantity.")
  quantity: int32;
}

@doc("Tax line request item.")
model InvoiceTaxLineRequestItem {
  @doc("Tax label.")
  label: string;
  @doc("Tax rate percentage.")
  rate: int32;
  @doc("Whether the tax is inclusive.")
  inclusive: boolean;
}

@doc("Paid amount entry used during settlement.")
model PaidAmountRequestItem {
  @doc("Paid amount.")
  amount: int32;
  @doc("Currency for the paid amount.")
  currency: CurrencyCode;
}

@doc("Request body for provisioning a monetization account.")
model ProvisionMonetizationAccountRequestBody {
  @doc("Account identifier to provision.")
  accountId: Uuid;
}

@doc("Request body for onboarding a seller.")
model OnboardSellerRequestBody {
  @doc("Seller email address.")
  email: string;
  @doc("Country code used for Stripe onboarding.")
  countryCode: SellerOnboardingCountryCode;
  @doc("Refresh URL returned to Stripe Connect.")
  refreshUrl: string;
  @doc("Return URL returned to Stripe Connect.")
  returnUrl: string;
}

@doc("Request body for registering a payment method.")
model RegisterPaymentMethodRequestBody {
  @doc("Gateway payment method identifier.")
  paymentMethodId: string;
  @doc("Payment method type.")
  type: AccountPaymentMethodType;
}

@doc("Request body for syncing a payout account webhook payload.")
model SyncPayoutAccountRequestBody {
  @doc("Connected account identifier from the payment gateway.")
  connectedAccountId: string;
  @doc("External account identifier from the payment gateway.")
  externalAccountId: string;
  @doc("Gateway event type.")
  eventType: string;
  @doc("Bank name.")
  bankName?: string | null;
  @doc("Last four digits of the payout account.")
  last4?: string | null;
  @doc("Country code reported by the gateway.")
  country?: string | null;
  @doc("Currency reported by the gateway.")
  currency?: string | null;
  @doc("Account holder type reported by the gateway.")
  accountHolderType?: AccountHolderType | null;
  @doc("Whether the payout account is the default one.")
  isDefault?: boolean;
}

@doc("Request body for authorizing a payment.")
model AuthorizePaymentRequestBody {
  @doc("Order identifier.")
  orderId: Uuid;
  @doc("Buyer monetization account identifier.")
  buyerMonetizationAccountId: Uuid;
  @doc("Payment amount.")
  amount: int32;
  @doc("Payment currency.")
  currency: CurrencyCode;
  @doc("Payment method identifier.")
  paymentMethodId: Uuid;
  @doc("Payment method type.")
  paymentMethodType: PaymentMethodType;
  @doc("Human-readable payment method label.")
  paymentMethodLabel: string;
  @doc("Whether recurring usage is enabled.")
  paymentMethodRecurringEnabled: boolean;
}

@doc("Request body for refunding a payment.")
model RefundPaymentRequestBody {
  @doc("Refund amount.")
  refundAmount: int32;
  @doc("Refund currency.")
  refundCurrency: CurrencyCode;
  @doc("Refund reason.")
  reason: string;
}

@doc("Request body for creating an invoice.")
model CreateInvoiceRequestBody {
  @doc("Order identifier.")
  orderIdentifier: Uuid;
  @doc("Buyer monetization account identifier.")
  buyerMonetizationAccountIdentifier: Uuid;
  @doc("Invoice lines.")
  lines: InvoiceLineRequestItem[];
  @doc("Shipping cost amount.")
  shippingCostAmount: int32;
  @doc("Invoice currency.")
  currency: CurrencyCode;
  @doc("Optional discount percentage.")
  discountPercentage?: int32 | null;
  @doc("Optional discount code.")
  discountCode?: string | null;
  @doc("Optional tax lines.")
  taxLines?: InvoiceTaxLineRequestItem[] | null;
  @doc("Seller country code.")
  sellerCountry: string;
  @doc("Whether the seller is tax registered.")
  sellerRegistered: boolean;
  @doc("Whether a qualified invoice is required.")
  qualifiedInvoiceRequired: boolean;
  @doc("Buyer country code.")
  buyerCountry: string;
  @doc("Whether the buyer is a business.")
  buyerIsBusiness: boolean;
  @doc("Whether the invoice is paid by card.")
  paidByCard: boolean;
  @doc("Optional seller registration number.")
  registrationNumber?: string | null;
}

@doc("Request body for recording a payment against an invoice.")
model RecordPaymentRequestBody {
  @doc("Captured payment identifier.")
  paymentIdentifier: Uuid;
}

@doc("Request body for settling revenue.")
model SettleRevenueRequestBody {
  @doc("Settlement schedule identifier.")
  settlementScheduleId: Uuid;
  @doc("Paid amount breakdown used for settlement.")
  paidAmounts: PaidAmountRequestItem[];
  @doc("Gateway fee rate percentage.")
  gatewayFeeRate: int32;
  @doc("Platform fee rate percentage.")
  platformFeeRate: int32;
  @doc("Optional fixed fee amount.")
  fixedFeeAmount?: int32 | null;
  @doc("Currency for the optional fixed fee.")
  fixedFeeCurrency?: CurrencyCode | null;
  @doc("Settlement period start date.")
  periodStart: DateString;
  @doc("Settlement period end date.")
  periodEnd: DateString;
}

@doc("Response returned when a monetization account is provisioned.")
model ProvisionMonetizationAccountResponse {
  @statusCode statusCode: 201;
  @body body: MonetizationAccountSummary;
}

@doc("Response returned when seller onboarding succeeds.")
model OnboardSellerResponse {
  @statusCode statusCode: 200;
  @body body: SellerOnboardingSummary;
}

@doc("Response returned when a payment method is registered.")
model RegisterPaymentMethodResponse {
  @statusCode statusCode: 201;
  @body body: RegisteredPaymentMethodSummary;
}

@doc("Response returned when a command succeeds without a payload.")
model OkEmptyArrayResponse {
  @statusCode statusCode: 200;
  @body body: EmptyJsonArray;
}

@doc("Response returned when a payment command creates a payment.")
model CreatedPaymentResponse {
  @statusCode statusCode: 201;
  @body body: PaymentSummary;
}

@doc("Response returned when a payment command succeeds.")
model OkPaymentResponse {
  @statusCode statusCode: 200;
  @body body: PaymentSummary;
}

@doc("Response returned when an invoice is created.")
model CreateInvoiceResponse {
  @statusCode statusCode: 201;
  @body body: InvoiceSummary;
}

@doc("Response returned when an invoice command succeeds.")
model OkInvoiceResponse {
  @statusCode statusCode: 200;
  @body body: InvoiceSummary;
}

@doc("Response returned when revenue settlement succeeds.")
model SettleRevenueResponse {
  @statusCode statusCode: 201;
  @body body: SettlementResultSummary;
}

@route("/accounts")
interface MonetizationAccountOperations {
  @post
  @doc("Provision a monetization account for an existing account.")
  provisionMonetizationAccount(
    @body body: ProvisionMonetizationAccountRequestBody,
  ): ProvisionMonetizationAccountResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{monetizationAccountId}/onboard-seller")
  @doc("Create a seller onboarding URL for the specified monetization account.")
  onboardSeller(
    @path monetizationAccountId: Uuid,
    @body body: OnboardSellerRequestBody,
  ): OnboardSellerResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{monetizationAccountId}/register-payment-method")
  @doc("Register a payment method on the specified monetization account.")
  registerPaymentMethod(
    @path monetizationAccountId: Uuid,
    @body body: RegisterPaymentMethodRequestBody,
  ): RegisterPaymentMethodResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/sync-payout-account")
  @doc("Synchronize payout account data from a gateway event payload.")
  syncPayoutAccount(
    @body body: SyncPayoutAccountRequestBody,
  ): OkEmptyArrayResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}

@route("/payments")
interface MonetizationPaymentOperations {
  @post
  @route("/authorize")
  @doc("Authorize a payment for an order.")
  authorizePayment(
    @body body: AuthorizePaymentRequestBody,
  ): CreatedPaymentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{paymentId}/capture")
  @doc("Capture a previously authorized payment.")
  capturePayment(
    @path paymentId: Uuid,
  ): OkPaymentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{paymentId}/refund")
  @doc("Refund a captured payment.")
  refundPayment(
    @path paymentId: Uuid,
    @body body: RefundPaymentRequestBody,
  ): OkPaymentResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}

@route("/invoices")
interface MonetizationBillingOperations {
  @post
  @doc("Create an invoice for an order.")
  createInvoice(
    @body body: CreateInvoiceRequestBody,
  ): CreateInvoiceResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/{invoiceId}/record-payment")
  @doc("Record a captured payment against an invoice.")
  recordPayment(
    @path invoiceId: Uuid,
    @body body: RecordPaymentRequestBody,
  ): OkInvoiceResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.ConflictProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}

@route("/")
interface MonetizationSettlementOperations {
  @post
  @route("/transfers/{transferId}/execute")
  @doc("Execute a pending transfer.")
  executeTransfer(
    @path transferId: Uuid,
  ): OkEmptyArrayResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;

  @post
  @route("/settlements/settle-revenue")
  @doc("Create a settlement batch and transfer from paid amounts.")
  settleRevenue(
    @body body: SettleRevenueRequestBody,
  ): SettleRevenueResponse | KPool.Common.UnauthorizedProblemResponse | KPool.Common.NotFoundProblemResponse | KPool.Common.UnprocessableEntityProblemResponse | KPool.Common.InternalServerErrorProblemResponse;
}
