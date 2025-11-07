VentoryOne API
 v1 
[ Base URL: app.ventory.one/ ]
https://app.ventory.one/swagger/?format=openapi
Terms of service
Contact the developer
Schemes

https
Django info@feela.de Django Logout
Authorize
Filter by tag
Amazon Inbound Shipments

GET
​/api​/amz_inbound_shipment​/
api_amz_inbound_shipment_list

Parameters
Try it out
Name	Description
page
integer
(query)
A page number within the paginated result set.

page - A page number within the paginated result set.
created_after
string($date)
(query)
Only show Amazon Inbound Shipments after provided date (YYYY-MM-DD)

created_after - Only show Amazon Inbound Shipments after provided date (YYYY-MM-DD)
Responses
Response content type

application/json
Code	Description
200	
Example Value
Model
{
count*	integer
next	string($uri)
x-nullable: true
previous	string($uri)
x-nullable: true
results*	[Amazon Inbound Shipments{
description:	
AMZ_Inbound_Shipment(id, organization, owner, fba_send_in, ShipmentId, amz_internal_shipmentId, amazonReferenceId, inboundPlanId, ShipmentName, warehouse_shipment_status, ShipmentStatus, Creation_Status, Carton_Data, Carton_Data_at_last_submission, DestinationFulfillmentCenterId, inbound_shipment_destination_address, inbound_shipment_shipfrom_address, TransportFeeAmount, TransportFeeCurrency, TransportFeeVoidDeadline, tracking_ids, date_created_internally, shipment_status_change_log)

id	integer
title: ID
readOnly: true
destination_address	string
title: Destination address
readOnly: true
amazon_carton_feed_data	string
title: Amazon carton feed data
readOnly: true
fba_send_in*	Fba send in{...}
ShipmentId	string
title: ShipmentId
maxLength: 50
minLength: 1
amz_internal_shipmentId	string
title: Amz internal shipmentId
maxLength: 38
minLength: 1
x-nullable: true
amazonReferenceId	string
title: AmazonReferenceId
maxLength: 38
minLength: 1
x-nullable: true
inboundPlanId	string
title: InboundPlanId
maxLength: 38
minLength: 1
x-nullable: true
ShipmentName*	string
title: ShipmentName
maxLength: 300
minLength: 1
warehouse_shipment_status	string
title: Warehouse shipment status
Enum:
Array [ 5 ]
ShipmentStatus	string
title: ShipmentStatus
maxLength: 300
minLength: 1
DestinationFulfillmentCenterId	string
title: DestinationFulfillmentCenterId
maxLength: 250
minLength: 1
TransportFeeAmount	string($decimal)
title: TransportFeeAmount
TransportFeeCurrency	string
title: TransportFeeCurrency
maxLength: 5
minLength: 1
TransportFeeVoidDeadline	string($date-time)
title: TransportFeeVoidDeadline
x-nullable: true
shipment_status_change_log	Shipment status change log{...}
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
inbound_shipment_shipfrom_address	integer
title: Inbound shipment shipfrom address
x-nullable: true
amz_case_obj	[...]
 
}]
 
}
GET
​/api​/amz_inbound_shipment​/get_amazon_carton_feed_data​/
api_amz_inbound_shipment_get_amazon_carton_feed_data

Parameters
Try it out
Name	Description
page
integer
(query)
A page number within the paginated result set.

page - A page number within the paginated result set.
amz_shipment_id *
string
(query)
Shipment ID provided by Amazon e.g. FBA12G9SWB7G

amz_shipment_id - Shipment ID provided by Amazon e.g. FBA12G9SWB7G
Responses
Response content type

application/json
Code	Description
200	
Example Value
Model
{
count*	integer
next	string($uri)
x-nullable: true
previous	string($uri)
x-nullable: true
results*	[Amazon Inbound Shipments{
description:	
AMZ_Inbound_Shipment(id, organization, owner, fba_send_in, ShipmentId, amz_internal_shipmentId, amazonReferenceId, inboundPlanId, ShipmentName, warehouse_shipment_status, ShipmentStatus, Creation_Status, Carton_Data, Carton_Data_at_last_submission, DestinationFulfillmentCenterId, inbound_shipment_destination_address, inbound_shipment_shipfrom_address, TransportFeeAmount, TransportFeeCurrency, TransportFeeVoidDeadline, tracking_ids, date_created_internally, shipment_status_change_log)

id	integer
title: ID
readOnly: true
destination_address	string
title: Destination address
readOnly: true
amazon_carton_feed_data	string
title: Amazon carton feed data
readOnly: true
fba_send_in*	Fba send in{...}
ShipmentId	string
title: ShipmentId
maxLength: 50
minLength: 1
amz_internal_shipmentId	string
title: Amz internal shipmentId
maxLength: 38
minLength: 1
x-nullable: true
amazonReferenceId	string
title: AmazonReferenceId
maxLength: 38
minLength: 1
x-nullable: true
inboundPlanId	string
title: InboundPlanId
maxLength: 38
minLength: 1
x-nullable: true
ShipmentName*	string
title: ShipmentName
maxLength: 300
minLength: 1
warehouse_shipment_status	string
title: Warehouse shipment status
Enum:
Array [ 5 ]
ShipmentStatus	string
title: ShipmentStatus
maxLength: 300
minLength: 1
DestinationFulfillmentCenterId	string
title: DestinationFulfillmentCenterId
maxLength: 250
minLength: 1
TransportFeeAmount	string($decimal)
title: TransportFeeAmount
TransportFeeCurrency	string
title: TransportFeeCurrency
maxLength: 5
minLength: 1
TransportFeeVoidDeadline	string($date-time)
title: TransportFeeVoidDeadline
x-nullable: true
shipment_status_change_log	Shipment status change log{...}
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
inbound_shipment_shipfrom_address	integer
title: Inbound shipment shipfrom address
x-nullable: true
amz_case_obj	[...]
 
}]
 
}
POST
​/api​/amz_inbound_shipment​/update_tracking_ids​/
api_amz_inbound_shipment_update_tracking_ids

Parameters
Try it out
Name	Description
data *
array[string]
(body)
Example Value
Model
[
Provide a complete list of tracking ids for the Amazon Inbound Shipment. Make sure that the order of the tracking ids matches the order of the cartons in the Amazon Carton Feed Data exactly. Otherwise there will be a mismatch of carton and tracking id. If you are unsure about the order, use the 'get_amazon_carton_feed_data' endpoint to check.

string
The tracking id of one carton. e.g. "1Z12345E0205271688"

]
amz_shipment_id *
string
(query)
Shipment ID provided by Amazon e.g. FBA12G9SWB7G

amz_shipment_id - Shipment ID provided by Amazon e.g. FBA12G9SWB7G
Responses
Response content type

application/json
Code	Description
201	
Example Value
Model
[
Provide a complete list of tracking ids for the Amazon Inbound Shipment. Make sure that the order of the tracking ids matches the order of the cartons in the Amazon Carton Feed Data exactly. Otherwise there will be a mismatch of carton and tracking id. If you are unsure about the order, use the 'get_amazon_carton_feed_data' endpoint to check.

string
The tracking id of one carton. e.g. "1Z12345E0205271688"

]
Current Stock

GET
​/api​/current_stock​/{category}​/
api_get_current_stock

Get the current stock by SKU category, or 'All' for all SKUs.

Parameters
Try it out
Name	Description
category *
string
(path)
category
Responses
Response content type

application/json
Code	Description
200	
Warehouse Transactions

POST
​/api​/external_warehouse_transaction​/
api_create_external_warehouse_transaction

Create an external warehouse transaction.

Parameters
Try it out
Name	Description
data *
object
(body)
Example Value
Model
{
line_item_identifier_type*	string
data*	string
 
}
Responses
Response content type

application/json
Code	Description
201	
Example Value
Model
{
line_item_identifier_type*	string
data*	string
 
}
POST
​/api​/warehouse_current_stock_line_items​/
api_warehouse_current_stock_line_items_create

Create a Warehouse Inbound Line Item.

Parameters
Try it out
No parameters

Responses
Response content type

application/json
Code	Description
201	
POST
​/api​/warehouse_inbound_line_item​/
api_create_inbound_line_item

Create a Warehouse Inbound Line Item.

Parameters
Try it out
Name	Description
data *
object
(body)
Example Value
Model
{
line_item_identifier_type*	string
data*	string
 
}
Responses
Response content type

application/json
Code	Description
201	
Example Value
Model
{
line_item_identifier_type*	string
data*	string
 
}
POST
​/api​/warehouse_outbound_line_item​/
api_create_outbound_line_item

Create a Warehouse Outbound Line Item.

Parameters
Try it out
Name	Description
data *
object
(body)
Example Value
Model
{
line_item_identifier_type*	string
data*	string
 
}
Responses
Response content type

application/json
Code	Description
201	
Example Value
Model
{
line_item_identifier_type*	string
data*	string
 
}
POST
​/api​/warehouse_outbound_line_items​/
api_warehouse_outbound_line_items_create

Create warehouse outbound line items.

Parameters
Try it out
No parameters

Responses
Response content type

application/json
Code	Description
201	
POST
​/api​/warehouse_transaction_external​/
api_warehouse_transaction_external_create

Create an external warehouse transaction.

Parameters
Try it out
Name	Description
data *
object
(body)
Example Value
Model
{
warehouse_shipment_status*	string
 
}
Responses
Response content type

application/json
Code	Description
201	
Example Value
Model
{
warehouse_shipment_status*	string
 
}
FBA Send Ins

GET
​/api​/fba_send_in_line_items​/
api_fba_send_in_line_items_list

Returns a paginated response of all FBA Send Ins, including detail of all corresponding Amazon
Inbound Shipments and line items.

Parameters
Try it out
Name	Description
page
integer
(query)
A page number within the paginated result set.

page - A page number within the paginated result set.
include_line_items
boolean
(query)
Include line items in the response

Default value : true


true
include_only_successfully_synchronized
boolean
(query)
Only show FBA Send Ins if they have been synchronized with Amazon successfully

Default value : true


true
created_after
string($date)
(query)
Only show FBA Send Ins after provided date (YYYY-MM-DD)

created_after - Only show FBA Send Ins after provided date (YYYY-MM-DD)
fba_send_in_status_filter
string
(query)
Only show FBA Send Ins with specific status. Multiple statuses can be provided as a comma separated list. Options are: Planned, Committed

Default value : Committed

Committed
category_filter
string
(query)
Only show FBA Send Ins with specific category. Multiple statuses can be provided as a comma separated list. Options are: regular, stock_correction, warehouse_transfer

Default value : regular

regular
Responses
Response content type

application/json
Code	Description
200	
Example Value
Model
{
count*	integer
next	string($uri)
x-nullable: true
previous	string($uri)
x-nullable: true
results*	[FBA Send Ins{
description:	
FBA_Send_In(id, organization, owner, fba_send_in_name, date_added, date_committed, datetime_amazon_sync_successful, fba_send_in_level_error_msg, abort_if_amazon_requires_to_split_shipment, fba_send_in_notes, fba_send_in_status, api_processing_status, Grouped_Shipments_dict, archived, hand_over_to_agency, default_email_settings, inbound_shipment_shipfromaddress, warehouse, target_marketplace, is_conversion_to_loose_stock, selected_carrier, expected_fba_send_in_delivery_time_in_days, wait_for_manual_shipment_finalization, auto_set_fba_send_in_to_shipped, warehouse_processing_status, multi_user_fba_send_in, category, custom_number, chart_updated, horizontal_chart, display_type)

id	integer
title: ID
readOnly: true
amz_inbound_shipments	[...]
fba_send_in_name*	string
title: Fba send in name
maxLength: 300
minLength: 1
date_added	string($date-time)
title: Date added
readOnly: true
date_committed	string($date-time)
title: Date committed
x-nullable: true
datetime_amazon_sync_successful	string($date-time)
title: Datetime amazon sync successful
x-nullable: true
fba_send_in_level_error_msg	string
title: Fba send in level error msg
maxLength: 2000
abort_if_amazon_requires_to_split_shipment	boolean
title: Abort if amazon requires to split shipment
fba_send_in_notes	string
title: Fba send in notes
maxLength: 2000
fba_send_in_status	string
title: Fba send in status
Enum:
Array [ 3 ]
api_processing_status	string
title: Api processing status
x-nullable: true
Enum:
Array [ 4 ]
archived	boolean
title: Archived
hand_over_to_agency	string
title: Hand over to agency
maxLength: 300
target_marketplace	string
title: Target marketplace
maxLength: 30
minLength: 1
is_conversion_to_loose_stock	boolean
title: Is conversion to loose stock
selected_carrier	string
title: Selected carrier
Enum:
Array [ 5 ]
expected_fba_send_in_delivery_time_in_days	integer
title: Expected fba send in delivery time in days
maximum: 2147483647
minimum: 0
wait_for_manual_shipment_finalization	boolean
title: Wait for manual shipment finalization
auto_set_fba_send_in_to_shipped	boolean
title: Auto set fba send in to shipped
warehouse_processing_status	string
title: Warehouse processing status
Enum:
Array [ 4 ]
category	string
title: Category
Enum:
Array [ 5 ]
custom_number	string
title: Custom number
maxLength: 300
minLength: 1
x-nullable: true
chart_updated	string($date-time)
title: Chart updated
x-nullable: true
horizontal_chart	Horizontal chart{...}
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
inbound_shipment_shipfromaddress	integer
title: Inbound shipment shipfromaddress
x-nullable: true
warehouse	integer
title: Warehouse
x-nullable: true
 
}]
 
}
Warehouse API

POST
​/api​/import_sales​/
Import sales data for a given organization.
api_import_sales

Import sales data.

Parameters
Try it out
Name	Description
data *
object
(body)
Example Value
Model
{
sales_data	[{
date	string($date)
example: 2024-02-27
sales_channel	string
sku	string
example: FL-S-CS-3538-MU-5
quantity	integer
 
}]
 
}
Responses
Response content type

application/json
Code	Description
200	
success

POST
​/api​/update_loose_stock​/
Update inventory for a given organization, warehouse and SKU.
api_update_loose_stock

Update inventory for a warehouse, and SKU quantities.

Parameters
Try it out
Name	Description
data *
object
(body)
Example Value
Model
{
warehouse_id*	integer
sku_qty_list*	[{
sku_id	integer
nullable: true
The ID of the SKU (required if sku is not provided).

sku	string
nullable: true
The SKU value (required if sku_id is not provided).

pcs_in_loose_stock*	integer
 
}]
 
}
Responses
Response content type

application/json
Code	Description
200	
Example Value
Model
{
message	string
 
}
POST
​/api​/update_plain_carton_line_item_qty​/
Update plain carton line item quatity for a given organization, warehouse and SKU.
api_update_plain_carton_line_item_qty

Update inventory for a warehouse, and SKU quantities .

Parameters
Try it out
Name	Description
data *
object
(body)
Example Value
Model
{
warehouse_id*	integer
sku_qty_list*	[{
sku_id	integer
nullable: true
The ID of the SKU (required if sku is not provided).

sku	string
nullable: true
The SKU value (required if sku_id is not provided).

carton_qty*	integer
nullable: false
Quantity of cartons.

 
}]
 
}
Responses
Response content type

application/json
Code	Description
200	
Example Value
Model
{
message	string
 
}
GET
​/api​/warehouse_shipment_status​/{id}​/
api_warehouse_shipment_status_read

Get the status of a warehouse shipment by its ID

Parameters
Try it out
Name	Description
id *
string
(path)
id
Responses
Response content type

application/json
Code	Description
200	
Example Value
Model
{
 
}
PUT
​/api​/warehouse_shipment_status​/{id}​/
api_warehouse_shipment_status_update

Update the status of a warehouse shipment by its ID.
Valid Status Choices:
- no_response
- request_received
- packed
- shipped
- closed

Parameters
Try it out
Name	Description
data *
object
(body)
Example Value
Model
{
warehouse_shipment_status*	string
 
}
id *
string
(path)
id
Responses
Response content type

application/json
Code	Description
200	
Example Value
Model
{
warehouse_shipment_status*	string
 
}
GET
​/api​/{id}​/warehouse_stock​/
api_warehouse_stock_on_line_item_level

Get the line item stock detail of a specific warehouse by ID.

Parameters
Try it out
Name	Description
id *
string
(path)
id
Responses
Response content type

application/json
Code	Description
200	
Purchase Orders

GET
​/api​/purchase_orders​/
api_purchase_orders_list

Returns a paginated response of all Purchase Orders, including detail of all corresponding line items.

Parameters
Try it out
Name	Description
page
integer
(query)
A page number within the paginated result set.

page - A page number within the paginated result set.
date_added
string($date)
(query)
Only show Purchase Orders after provided date (YYYY-MM-DD)

date_added - Only show Purchase Orders after provided date (YYYY-MM-DD)
purchase_order_status
string
(query)
Only show Purchase Orders with specific status. Multiple statuses can be provided as a comma separated list. Options are: Planned, Ordered, Shipped, Received

purchase_order_status - Only show Purchase Orders with specific status. Multiple statuses can be provided as a comma separated list. Options are: Planned, Ordered, Shipped, Received
Responses
Response content type

application/json
Code	Description
200	
Example Value
Model
{
count*	integer
next	string($uri)
x-nullable: true
previous	string($uri)
x-nullable: true
results*	[Purchase_Order_Incl_Line_Items_{
description:	
Purchase_Order(id, organization, owner, order_name, order_placed_date, order_shipped_date, order_received_date, date_added, supplier, supplier_address, billing_address, ship_to_address, po_number, departure_port, inco_term, payment_terms, additional_agreements, purchase_currency, order_notes, status, archived, is_loose_stock_to_carton_conversion, is_bundle_dissolving, is_bundle_assembly, loose_stock_conversion_sourcing_plan, warehouse_transfer_fba_send_in_source_id, warehouse, agl_shipment_link, display_type, transport_mode, category, custom_status, virtual_po)

id	integer
title: ID
readOnly: true
mixed_cartons	[...]
plain_cartons	[...]
order_name*	string
title: Order name
maxLength: 300
minLength: 1
order_placed_date	string($date)
title: Order placed date
x-nullable: true
order_shipped_date	string($date)
title: Order shipped date
x-nullable: true
order_received_date	string($date)
title: Order received date
x-nullable: true
date_added	string($date-time)
title: Date added
readOnly: true
supplier_address	string
title: Supplier address
maxLength: 300
minLength: 1
x-nullable: true
billing_address	string
title: Billing address
maxLength: 300
minLength: 1
x-nullable: true
ship_to_address	string
title: Ship to address
maxLength: 300
minLength: 1
x-nullable: true
po_number	string
title: Po number
maxLength: 300
minLength: 1
x-nullable: true
departure_port	string
title: Departure port
maxLength: 300
minLength: 1
x-nullable: true
inco_term	string
title: Inco term
x-nullable: true
Enum:
Array [ 13 ]
payment_terms	string
title: Payment terms
maxLength: 1000
minLength: 1
x-nullable: true
additional_agreements	string
title: Additional agreements
maxLength: 10000
minLength: 1
x-nullable: true
purchase_currency	string
title: Purchase currency
maxLength: 20
minLength: 1
order_notes	string
title: Order notes
maxLength: 2000
status	string
title: Status
Enum:
Array [ 4 ]
archived	boolean
title: Archived
is_loose_stock_to_carton_conversion	boolean
title: Is loose stock to carton conversion
is_bundle_dissolving	boolean
title: Is bundle dissolving
is_bundle_assembly	boolean
title: Is bundle assembly
transport_mode	string
title: Transport mode
Enum:
Array [ 6 ]
category	string
title: Category
Enum:
Array [ 5 ]
custom_status	string
title: Custom status
maxLength: 50
x-nullable: true
virtual_po	boolean
title: Virtual po
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
supplier	integer
title: Supplier
x-nullable: true
warehouse	integer
title: Warehouse
x-nullable: true
agl_shipment_link	integer
title: Agl shipment link
x-nullable: true
 
}]
 
}
SKUs

GET
​/api​/skus​/
api_skus_list

Returns a paginated response of all SKUs and their attributes.

Parameters
Try it out
Name	Description
page
integer
(query)
A page number within the paginated result set.

page - A page number within the paginated result set.
Responses
Response content type

application/json
Code	Description
200	
Example Value
Model
{
count*	integer
next	string($uri)
x-nullable: true
previous	string($uri)
x-nullable: true
results*	[SKU{
description:	
SKU(id, organization, owner, status, forecasting_enabled, auto_send_in_suggestion, amz_fulfillment_type, amz_product_type_name, amz_product_group, amz_product_binding, sku, ASIN, Parent_ASIN, FNSKU, small_image_url, man_small_image_url, stock_by_warehouse_cached, wh_pcs_left_cached, pcs_on_the_way_cached, bundle_wh_pcs_left_cached, bundle_pcs_on_the_way_cached, amz_condition, synced_with_sku_obj, aggregated_to_parent_sku, is_bundle_parent_sku, is_bundle_child_sku, to_be_notified_email, custom_product_id, def_product_name_for_invoice, def_purchase_price, moneyback_purchase_price, def_landed_cost_EUR, avg_landed_cost_EUR_cached, avg_landed_cost_at_fba_EUR_cached, avg_landed_cost_in_stock_EUR_cached, avg_landed_cost_on_the_way_EUR_cached, date_updated_fba_data, fba_oos_history_adjustment_enabled, amz_per_unit_volume, afn_inbound_manual_correction_quantity, afn_inbound_pcs_on_hold, source_amazon_de, source_amazon_fr, source_amazon_it, source_amazon_es, source_amazon_co_uk, source_shopify, source_ebay, amz_selling_price_EUR, amz_product_id, Color, Size, date_updated_parent_child_relationships, cat_product_type, cat_color, cat_size, cat_shape, overview_category, max_allowed_send_in_pcs, target_reach_fba_in_days, fbm_target_reach_in_days, target_reach_total_in_days, lead_time_fba_in_days, fbm_lead_time_in_days, lead_time_re_order_in_days, replenishment_frequency_fba_in_days, fbm_replenishment_frequency_in_days, replenishment_frequency_re_order_in_days, sales_last_30_days_manual, min_fba_stock, min_fbm_stock, fba_send_in_priority_asc, moq, re_order_logic, weighting_sales_last_3_days, weighting_sales_last_7_days, weighting_sales_last_30_days, weighting_sales_last_90_days, weighting_sales_last_180_days, weighting_sales_last_365_days, weighting_sales_last_180_to_365_days, re_order_weighting_sales_last_3_days, re_order_weighting_sales_last_7_days, re_order_weighting_sales_last_30_days, re_order_weighting_sales_last_90_days, re_order_weighting_sales_last_180_days, re_order_weighting_sales_last_365_days, re_order_weighting_sales_last_180_to_365_days, oos_days_last_3_days, oos_days_last_7_days, oos_days_last_30_days, oos_days_last_90_days, oos_days_last_180_days, oos_days_last_365_days, oos_days_last_180_to_365_days, date_updated_sales_data, amz_listing_status, amz_title, amz_sync_status, amz_length_in_cm, amz_width_in_cm, amz_height_in_cm, amz_weight_in_kg, man_length_in_cm, man_width_in_cm, man_height_in_cm, man_weight_in_kg, def_pcs_per_carton, def_c_length, def_c_width, def_c_height, def_c_weight, def_amz_prep_instructions_labelling, def_amz_prep_instructions_preparation, def_additional_amz_prep_instructions, notes, use_custom_measurements, amazon_product_packaging, avg_price, avg_profitability, avg_fees, seasonal_forecasting_enabled, seasonal_coefficients, seasonal_forecasting_fbm_enabled, seasonal_coefficients_fbm, specific_display_name, specific_classification_id, top_level_display_name, top_level_classification_id)

id	integer
title: ID
readOnly: true
status	string
title: Status
Enum:
Array [ 3 ]
amz_fulfillment_type	string
title: Amz fulfillment type
x-nullable: true
Enum:
Array [ 2 ]
sku*	string
title: Sku
maxLength: 300
minLength: 1
ASIN	string
title: ASIN
maxLength: 300
minLength: 1
x-nullable: true
Parent_ASIN	string
title: Parent ASIN
maxLength: 300
minLength: 1
x-nullable: true
FNSKU	string
title: FNSKU
maxLength: 300
minLength: 1
x-nullable: true
small_image_url	string
title: Small image url
minLength: 1
x-nullable: true
man_small_image_url	string
title: Man small image url
x-nullable: true
to_be_notified_email	string
title: To be notified email
maxLength: 500
custom_product_id	string
title: Custom product id
maxLength: 300
def_product_name_for_invoice	string
title: Def product name for invoice
maxLength: 300
amz_per_unit_volume	number
title: Amz per unit volume
x-nullable: true
afn_inbound_manual_correction_quantity	integer
title: Afn inbound manual correction quantity
maximum: 2147483647
minimum: -2147483648
x-nullable: true
source_amazon_de	string
title: Source amazon de
Enum:
Array [ 8 ]
source_amazon_fr	string
title: Source amazon fr
Enum:
Array [ 8 ]
source_amazon_it	string
title: Source amazon it
Enum:
Array [ 8 ]
source_amazon_es	string
title: Source amazon es
Enum:
Array [ 8 ]
source_amazon_co_uk	string
title: Source amazon co uk
Enum:
Array [ 8 ]
amz_selling_price_EUR	string($decimal)
title: Amz selling price EUR
amz_product_id	string
title: Amz product id
maxLength: 300
cat_product_type	string
title: Cat product type
maxLength: 300
x-nullable: true
cat_color	string
title: Cat color
maxLength: 300
x-nullable: true
cat_size	string
title: Cat size
maxLength: 300
x-nullable: true
cat_shape	string
title: Cat shape
maxLength: 300
x-nullable: true
overview_category	string
title: Overview category
maxLength: 300
target_reach_fba_in_days	integer
title: Target reach fba in days
maximum: 2147483647
minimum: -2147483648
target_reach_total_in_days	integer
title: Target reach total in days
maximum: 2147483647
minimum: -2147483648
lead_time_fba_in_days	integer
title: Lead time fba in days
maximum: 2147483647
minimum: -2147483648
lead_time_re_order_in_days	integer
title: Lead time re order in days
maximum: 2147483647
minimum: -2147483648
replenishment_frequency_fba_in_days	integer
title: Replenishment frequency fba in days
maximum: 2147483647
minimum: -2147483648
replenishment_frequency_re_order_in_days	integer
title: Replenishment frequency re order in days
maximum: 2147483647
minimum: -2147483648
sales_last_30_days_manual	integer
title: Sales last 30 days manual
maximum: 2147483647
minimum: -2147483648
min_fba_stock	integer
title: Min fba stock
maximum: 2147483647
minimum: -2147483648
fba_send_in_priority_asc	integer
title: Fba send in priority asc
maximum: 2147483647
minimum: -2147483648
moq	integer
title: Moq
maximum: 2147483647
minimum: 0
re_order_logic	string
title: Re order logic
Enum:
Array [ 2 ]
weighting_sales_last_3_days	number
title: Weighting sales last 3 days
weighting_sales_last_7_days	number
title: Weighting sales last 7 days
weighting_sales_last_30_days	number
title: Weighting sales last 30 days
weighting_sales_last_90_days	number
title: Weighting sales last 90 days
weighting_sales_last_180_days	number
title: Weighting sales last 180 days
weighting_sales_last_365_days	number
title: Weighting sales last 365 days
amz_title	string
title: Amz title
minLength: 1
x-nullable: true
amz_length_in_cm	number
title: Amz length in cm
x-nullable: true
amz_width_in_cm	number
title: Amz width in cm
x-nullable: true
amz_height_in_cm	number
title: Amz height in cm
x-nullable: true
amz_weight_in_kg	number
title: Amz weight in kg
x-nullable: true
man_length_in_cm	number
title: Man length in cm
x-nullable: true
man_width_in_cm	number
title: Man width in cm
x-nullable: true
man_height_in_cm	number
title: Man height in cm
x-nullable: true
man_weight_in_kg	number
title: Man weight in kg
x-nullable: true
def_pcs_per_carton	integer
title: Def pcs per carton
maximum: 2147483647
minimum: -2147483648
x-nullable: true
def_c_length	string($decimal)
title: Def c length
x-nullable: true
def_c_width	string($decimal)
title: Def c width
x-nullable: true
def_c_height	string($decimal)
title: Def c height
x-nullable: true
def_c_weight	string($decimal)
title: Def c weight
x-nullable: true
def_amz_prep_instructions_labelling	string
title: Def amz prep instructions labelling
Enum:
Array [ 3 ]
organization*	integer
title: Organization
re_order_template	[...]
def_landed_cost_EUR	string($decimal)
title: Def landed cost EUR
def_purchase_price	string($decimal)
title: Def purchase price
 
}]
 
}
Models
Warehouse{
description:	
Warehouse(id, organization, owner, warehouse_name, fba_send_in_priority, default_email_settings, inbound_shipment_shipfromaddress, selected_carrier, expected_fba_send_in_delivery_time_in_days, def_wait_for_manual_shipment_finalization, auto_set_fba_send_in_to_shipped, is_default, abort_if_amazon_requires_to_split_shipment, mobile_mgmt_filter_preset, mobile_mgmt_filter_inbound_purchase_order, distribution_warehouse, warehouse_type, allow_sourcing_from_bundle_parents, negative_loose_stock_handling, customer_id_at_3pl)

id	integer
title: ID
readOnly: true
warehouse_name	string
title: Warehouse name
maxLength: 300
minLength: 1
 
}
Fba send in{
description:	
FBA_Send_In(id, organization, owner, fba_send_in_name, date_added, date_committed, datetime_amazon_sync_successful, fba_send_in_level_error_msg, abort_if_amazon_requires_to_split_shipment, fba_send_in_notes, fba_send_in_status, api_processing_status, Grouped_Shipments_dict, archived, hand_over_to_agency, default_email_settings, inbound_shipment_shipfromaddress, warehouse, target_marketplace, is_conversion_to_loose_stock, selected_carrier, expected_fba_send_in_delivery_time_in_days, wait_for_manual_shipment_finalization, auto_set_fba_send_in_to_shipped, warehouse_processing_status, multi_user_fba_send_in, category, custom_number, chart_updated, horizontal_chart, display_type)

datetime_amazon_sync_successful	string($date-time)
title: Datetime amazon sync successful
x-nullable: true
fba_send_in_notes	string
title: Fba send in notes
maxLength: 2000
fba_send_in_status	string
title: Fba send in status
Enum:
Array [ 3 ]
target_marketplace	string
title: Target marketplace
maxLength: 30
minLength: 1
selected_carrier	string
title: Selected carrier
Enum:
Array [ 5 ]
warehouse	Warehouse{
description:	
Warehouse(id, organization, owner, warehouse_name, fba_send_in_priority, default_email_settings, inbound_shipment_shipfromaddress, selected_carrier, expected_fba_send_in_delivery_time_in_days, def_wait_for_manual_shipment_finalization, auto_set_fba_send_in_to_shipped, is_default, abort_if_amazon_requires_to_split_shipment, mobile_mgmt_filter_preset, mobile_mgmt_filter_inbound_purchase_order, distribution_warehouse, warehouse_type, allow_sourcing_from_bundle_parents, negative_loose_stock_handling, customer_id_at_3pl)

id	integer
title: ID
readOnly: true
warehouse_name	string
title: Warehouse name
maxLength: 300
minLength: 1
 
}
 
}
Amazon Inbound Shipments{
description:	
AMZ_Inbound_Shipment(id, organization, owner, fba_send_in, ShipmentId, amz_internal_shipmentId, amazonReferenceId, inboundPlanId, ShipmentName, warehouse_shipment_status, ShipmentStatus, Creation_Status, Carton_Data, Carton_Data_at_last_submission, DestinationFulfillmentCenterId, inbound_shipment_destination_address, inbound_shipment_shipfrom_address, TransportFeeAmount, TransportFeeCurrency, TransportFeeVoidDeadline, tracking_ids, date_created_internally, shipment_status_change_log)

id	integer
title: ID
readOnly: true
destination_address	string
title: Destination address
readOnly: true
amazon_carton_feed_data	string
title: Amazon carton feed data
readOnly: true
fba_send_in*	Fba send in{
description:	
FBA_Send_In(id, organization, owner, fba_send_in_name, date_added, date_committed, datetime_amazon_sync_successful, fba_send_in_level_error_msg, abort_if_amazon_requires_to_split_shipment, fba_send_in_notes, fba_send_in_status, api_processing_status, Grouped_Shipments_dict, archived, hand_over_to_agency, default_email_settings, inbound_shipment_shipfromaddress, warehouse, target_marketplace, is_conversion_to_loose_stock, selected_carrier, expected_fba_send_in_delivery_time_in_days, wait_for_manual_shipment_finalization, auto_set_fba_send_in_to_shipped, warehouse_processing_status, multi_user_fba_send_in, category, custom_number, chart_updated, horizontal_chart, display_type)

datetime_amazon_sync_successful	string($date-time)
title: Datetime amazon sync successful
x-nullable: true
fba_send_in_notes	string
title: Fba send in notes
maxLength: 2000
fba_send_in_status	string
title: Fba send in status
Enum:
Array [ 3 ]
target_marketplace	string
title: Target marketplace
maxLength: 30
minLength: 1
selected_carrier	string
title: Selected carrier
Enum:
Array [ 5 ]
warehouse	Warehouse{
description:	
Warehouse(id, organization, owner, warehouse_name, fba_send_in_priority, default_email_settings, inbound_shipment_shipfromaddress, selected_carrier, expected_fba_send_in_delivery_time_in_days, def_wait_for_manual_shipment_finalization, auto_set_fba_send_in_to_shipped, is_default, abort_if_amazon_requires_to_split_shipment, mobile_mgmt_filter_preset, mobile_mgmt_filter_inbound_purchase_order, distribution_warehouse, warehouse_type, allow_sourcing_from_bundle_parents, negative_loose_stock_handling, customer_id_at_3pl)

id	integer
title: ID
readOnly: true
warehouse_name	string
title: Warehouse name
maxLength: 300
minLength: 1
 
}
 
}
ShipmentId	string
title: ShipmentId
maxLength: 50
minLength: 1
amz_internal_shipmentId	string
title: Amz internal shipmentId
maxLength: 38
minLength: 1
x-nullable: true
amazonReferenceId	string
title: AmazonReferenceId
maxLength: 38
minLength: 1
x-nullable: true
inboundPlanId	string
title: InboundPlanId
maxLength: 38
minLength: 1
x-nullable: true
ShipmentName*	string
title: ShipmentName
maxLength: 300
minLength: 1
warehouse_shipment_status	string
title: Warehouse shipment status
Enum:
Array [ 5 ]
ShipmentStatus	string
title: ShipmentStatus
maxLength: 300
minLength: 1
DestinationFulfillmentCenterId	string
title: DestinationFulfillmentCenterId
maxLength: 250
minLength: 1
TransportFeeAmount	string($decimal)
title: TransportFeeAmount
TransportFeeCurrency	string
title: TransportFeeCurrency
maxLength: 5
minLength: 1
TransportFeeVoidDeadline	string($date-time)
title: TransportFeeVoidDeadline
x-nullable: true
shipment_status_change_log	Shipment status change log{
 
}
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
inbound_shipment_shipfrom_address	integer
title: Inbound shipment shipfrom address
x-nullable: true
amz_case_obj	[
uniqueItems: true
integer]
 
}
FBA Send Ins{
description:	
FBA_Send_In(id, organization, owner, fba_send_in_name, date_added, date_committed, datetime_amazon_sync_successful, fba_send_in_level_error_msg, abort_if_amazon_requires_to_split_shipment, fba_send_in_notes, fba_send_in_status, api_processing_status, Grouped_Shipments_dict, archived, hand_over_to_agency, default_email_settings, inbound_shipment_shipfromaddress, warehouse, target_marketplace, is_conversion_to_loose_stock, selected_carrier, expected_fba_send_in_delivery_time_in_days, wait_for_manual_shipment_finalization, auto_set_fba_send_in_to_shipped, warehouse_processing_status, multi_user_fba_send_in, category, custom_number, chart_updated, horizontal_chart, display_type)

id	integer
title: ID
readOnly: true
amz_inbound_shipments	[
readOnly: true
Amazon Inbound Shipments{
description:	
AMZ_Inbound_Shipment(id, organization, owner, fba_send_in, ShipmentId, amz_internal_shipmentId, amazonReferenceId, inboundPlanId, ShipmentName, warehouse_shipment_status, ShipmentStatus, Creation_Status, Carton_Data, Carton_Data_at_last_submission, DestinationFulfillmentCenterId, inbound_shipment_destination_address, inbound_shipment_shipfrom_address, TransportFeeAmount, TransportFeeCurrency, TransportFeeVoidDeadline, tracking_ids, date_created_internally, shipment_status_change_log)

id	integer
title: ID
readOnly: true
destination_address	string
title: Destination address
readOnly: true
amazon_carton_feed_data	string
title: Amazon carton feed data
readOnly: true
fba_send_in*	Fba send in{...}
ShipmentId	string
title: ShipmentId
maxLength: 50
minLength: 1
amz_internal_shipmentId	string
title: Amz internal shipmentId
maxLength: 38
minLength: 1
x-nullable: true
amazonReferenceId	string
title: AmazonReferenceId
maxLength: 38
minLength: 1
x-nullable: true
inboundPlanId	string
title: InboundPlanId
maxLength: 38
minLength: 1
x-nullable: true
ShipmentName*	string
title: ShipmentName
maxLength: 300
minLength: 1
warehouse_shipment_status	string
title: Warehouse shipment status
Enum:
Array [ 5 ]
ShipmentStatus	string
title: ShipmentStatus
maxLength: 300
minLength: 1
DestinationFulfillmentCenterId	string
title: DestinationFulfillmentCenterId
maxLength: 250
minLength: 1
TransportFeeAmount	string($decimal)
title: TransportFeeAmount
TransportFeeCurrency	string
title: TransportFeeCurrency
maxLength: 5
minLength: 1
TransportFeeVoidDeadline	string($date-time)
title: TransportFeeVoidDeadline
x-nullable: true
shipment_status_change_log	Shipment status change log{...}
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
inbound_shipment_shipfrom_address	integer
title: Inbound shipment shipfrom address
x-nullable: true
amz_case_obj	[...]
 
}]
fba_send_in_name*	string
title: Fba send in name
maxLength: 300
minLength: 1
date_added	string($date-time)
title: Date added
readOnly: true
date_committed	string($date-time)
title: Date committed
x-nullable: true
datetime_amazon_sync_successful	string($date-time)
title: Datetime amazon sync successful
x-nullable: true
fba_send_in_level_error_msg	string
title: Fba send in level error msg
maxLength: 2000
abort_if_amazon_requires_to_split_shipment	boolean
title: Abort if amazon requires to split shipment
fba_send_in_notes	string
title: Fba send in notes
maxLength: 2000
fba_send_in_status	string
title: Fba send in status
Enum:
Array [ 3 ]
api_processing_status	string
title: Api processing status
x-nullable: true
Enum:
Array [ 4 ]
archived	boolean
title: Archived
hand_over_to_agency	string
title: Hand over to agency
maxLength: 300
target_marketplace	string
title: Target marketplace
maxLength: 30
minLength: 1
is_conversion_to_loose_stock	boolean
title: Is conversion to loose stock
selected_carrier	string
title: Selected carrier
Enum:
Array [ 5 ]
expected_fba_send_in_delivery_time_in_days	integer
title: Expected fba send in delivery time in days
maximum: 2147483647
minimum: 0
wait_for_manual_shipment_finalization	boolean
title: Wait for manual shipment finalization
auto_set_fba_send_in_to_shipped	boolean
title: Auto set fba send in to shipped
warehouse_processing_status	string
title: Warehouse processing status
Enum:
Array [ 4 ]
category	string
title: Category
Enum:
Array [ 5 ]
custom_number	string
title: Custom number
maxLength: 300
minLength: 1
x-nullable: true
chart_updated	string($date-time)
title: Chart updated
x-nullable: true
horizontal_chart	Horizontal chart{
 
x-nullable	true
}
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
inbound_shipment_shipfromaddress	integer
title: Inbound shipment shipfromaddress
x-nullable: true
warehouse	integer
title: Warehouse
x-nullable: true
 
}
Sku identifiers{
description:	
SKU(id, organization, owner, status, forecasting_enabled, auto_send_in_suggestion, amz_fulfillment_type, amz_product_type_name, amz_product_group, amz_product_binding, sku, ASIN, Parent_ASIN, FNSKU, small_image_url, man_small_image_url, stock_by_warehouse_cached, wh_pcs_left_cached, pcs_on_the_way_cached, bundle_wh_pcs_left_cached, bundle_pcs_on_the_way_cached, amz_condition, synced_with_sku_obj, aggregated_to_parent_sku, is_bundle_parent_sku, is_bundle_child_sku, to_be_notified_email, custom_product_id, def_product_name_for_invoice, def_purchase_price, moneyback_purchase_price, def_landed_cost_EUR, avg_landed_cost_EUR_cached, avg_landed_cost_at_fba_EUR_cached, avg_landed_cost_in_stock_EUR_cached, avg_landed_cost_on_the_way_EUR_cached, date_updated_fba_data, fba_oos_history_adjustment_enabled, amz_per_unit_volume, afn_inbound_manual_correction_quantity, afn_inbound_pcs_on_hold, source_amazon_de, source_amazon_fr, source_amazon_it, source_amazon_es, source_amazon_co_uk, source_shopify, source_ebay, amz_selling_price_EUR, amz_product_id, Color, Size, date_updated_parent_child_relationships, cat_product_type, cat_color, cat_size, cat_shape, overview_category, max_allowed_send_in_pcs, target_reach_fba_in_days, fbm_target_reach_in_days, target_reach_total_in_days, lead_time_fba_in_days, fbm_lead_time_in_days, lead_time_re_order_in_days, replenishment_frequency_fba_in_days, fbm_replenishment_frequency_in_days, replenishment_frequency_re_order_in_days, sales_last_30_days_manual, min_fba_stock, min_fbm_stock, fba_send_in_priority_asc, moq, re_order_logic, weighting_sales_last_3_days, weighting_sales_last_7_days, weighting_sales_last_30_days, weighting_sales_last_90_days, weighting_sales_last_180_days, weighting_sales_last_365_days, weighting_sales_last_180_to_365_days, re_order_weighting_sales_last_3_days, re_order_weighting_sales_last_7_days, re_order_weighting_sales_last_30_days, re_order_weighting_sales_last_90_days, re_order_weighting_sales_last_180_days, re_order_weighting_sales_last_365_days, re_order_weighting_sales_last_180_to_365_days, oos_days_last_3_days, oos_days_last_7_days, oos_days_last_30_days, oos_days_last_90_days, oos_days_last_180_days, oos_days_last_365_days, oos_days_last_180_to_365_days, date_updated_sales_data, amz_listing_status, amz_title, amz_sync_status, amz_length_in_cm, amz_width_in_cm, amz_height_in_cm, amz_weight_in_kg, man_length_in_cm, man_width_in_cm, man_height_in_cm, man_weight_in_kg, def_pcs_per_carton, def_c_length, def_c_width, def_c_height, def_c_weight, def_amz_prep_instructions_labelling, def_amz_prep_instructions_preparation, def_additional_amz_prep_instructions, notes, use_custom_measurements, amazon_product_packaging, avg_price, avg_profitability, avg_fees, seasonal_forecasting_enabled, seasonal_coefficients, seasonal_forecasting_fbm_enabled, seasonal_coefficients_fbm, specific_display_name, specific_classification_id, top_level_display_name, top_level_classification_id)

id	integer
title: ID
readOnly: true
sku*	string
title: Sku
maxLength: 300
minLength: 1
ASIN	string
title: ASIN
maxLength: 300
minLength: 1
x-nullable: true
Parent_ASIN	string
title: Parent ASIN
maxLength: 300
minLength: 1
x-nullable: true
FNSKU	string
title: FNSKU
maxLength: 300
minLength: 1
x-nullable: true
amz_title	string
title: Amz title
minLength: 1
x-nullable: true
amz_product_id	string
title: Amz product id
maxLength: 300
custom_product_id	string
title: Custom product id
maxLength: 300
def_product_name_for_invoice	string
title: Def product name for invoice
maxLength: 300
 
}
Mixed_Carton_Line_Item_{
description:	
Mixed_Carton_Line_Item(id, organization, owner, sku_obj, sku, pcs_per_carton, landed_cost_per_pc_EUR, purchase_price_per_pc, expiration_date, mixed_carton)

id	integer
title: ID
readOnly: true
sku_identifiers*	Sku identifiers{
description:	
SKU(id, organization, owner, status, forecasting_enabled, auto_send_in_suggestion, amz_fulfillment_type, amz_product_type_name, amz_product_group, amz_product_binding, sku, ASIN, Parent_ASIN, FNSKU, small_image_url, man_small_image_url, stock_by_warehouse_cached, wh_pcs_left_cached, pcs_on_the_way_cached, bundle_wh_pcs_left_cached, bundle_pcs_on_the_way_cached, amz_condition, synced_with_sku_obj, aggregated_to_parent_sku, is_bundle_parent_sku, is_bundle_child_sku, to_be_notified_email, custom_product_id, def_product_name_for_invoice, def_purchase_price, moneyback_purchase_price, def_landed_cost_EUR, avg_landed_cost_EUR_cached, avg_landed_cost_at_fba_EUR_cached, avg_landed_cost_in_stock_EUR_cached, avg_landed_cost_on_the_way_EUR_cached, date_updated_fba_data, fba_oos_history_adjustment_enabled, amz_per_unit_volume, afn_inbound_manual_correction_quantity, afn_inbound_pcs_on_hold, source_amazon_de, source_amazon_fr, source_amazon_it, source_amazon_es, source_amazon_co_uk, source_shopify, source_ebay, amz_selling_price_EUR, amz_product_id, Color, Size, date_updated_parent_child_relationships, cat_product_type, cat_color, cat_size, cat_shape, overview_category, max_allowed_send_in_pcs, target_reach_fba_in_days, fbm_target_reach_in_days, target_reach_total_in_days, lead_time_fba_in_days, fbm_lead_time_in_days, lead_time_re_order_in_days, replenishment_frequency_fba_in_days, fbm_replenishment_frequency_in_days, replenishment_frequency_re_order_in_days, sales_last_30_days_manual, min_fba_stock, min_fbm_stock, fba_send_in_priority_asc, moq, re_order_logic, weighting_sales_last_3_days, weighting_sales_last_7_days, weighting_sales_last_30_days, weighting_sales_last_90_days, weighting_sales_last_180_days, weighting_sales_last_365_days, weighting_sales_last_180_to_365_days, re_order_weighting_sales_last_3_days, re_order_weighting_sales_last_7_days, re_order_weighting_sales_last_30_days, re_order_weighting_sales_last_90_days, re_order_weighting_sales_last_180_days, re_order_weighting_sales_last_365_days, re_order_weighting_sales_last_180_to_365_days, oos_days_last_3_days, oos_days_last_7_days, oos_days_last_30_days, oos_days_last_90_days, oos_days_last_180_days, oos_days_last_365_days, oos_days_last_180_to_365_days, date_updated_sales_data, amz_listing_status, amz_title, amz_sync_status, amz_length_in_cm, amz_width_in_cm, amz_height_in_cm, amz_weight_in_kg, man_length_in_cm, man_width_in_cm, man_height_in_cm, man_weight_in_kg, def_pcs_per_carton, def_c_length, def_c_width, def_c_height, def_c_weight, def_amz_prep_instructions_labelling, def_amz_prep_instructions_preparation, def_additional_amz_prep_instructions, notes, use_custom_measurements, amazon_product_packaging, avg_price, avg_profitability, avg_fees, seasonal_forecasting_enabled, seasonal_coefficients, seasonal_forecasting_fbm_enabled, seasonal_coefficients_fbm, specific_display_name, specific_classification_id, top_level_display_name, top_level_classification_id)

id	integer
title: ID
readOnly: true
sku*	string
title: Sku
maxLength: 300
minLength: 1
ASIN	string
title: ASIN
maxLength: 300
minLength: 1
x-nullable: true
Parent_ASIN	string
title: Parent ASIN
maxLength: 300
minLength: 1
x-nullable: true
FNSKU	string
title: FNSKU
maxLength: 300
minLength: 1
x-nullable: true
amz_title	string
title: Amz title
minLength: 1
x-nullable: true
amz_product_id	string
title: Amz product id
maxLength: 300
custom_product_id	string
title: Custom product id
maxLength: 300
def_product_name_for_invoice	string
title: Def product name for invoice
maxLength: 300
 
}
sku	string
title: Sku
maxLength: 300
minLength: 1
pcs_per_carton	integer
title: Pcs per carton
maximum: 2147483647
minimum: 0
expiration_date	string($date)
title: Expiration date
x-nullable: true
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
sku_obj	integer
title: Sku obj
x-nullable: true
mixed_carton	integer
title: Mixed carton
x-nullable: true
 
}
Mixed_Carton_{
description:	
Mixed_Carton(id, organization, owner, purchase_order, cartons_left_cached, c_length, c_width, c_height, c_weight, to_be_printed_on_Label, original_qty_ctns_ordered, actual_qty_ctns_received, net_weight_kg, carton_note, qty_cartons, qty_cartons_not_found_before_send_in)

id	integer
title: ID
readOnly: true
cartons_left*	integer
title: Cartons left
line_items	[
readOnly: true
Mixed_Carton_Line_Item_{
description:	
Mixed_Carton_Line_Item(id, organization, owner, sku_obj, sku, pcs_per_carton, landed_cost_per_pc_EUR, purchase_price_per_pc, expiration_date, mixed_carton)

id	integer
title: ID
readOnly: true
sku_identifiers*	Sku identifiers{...}
sku	string
title: Sku
maxLength: 300
minLength: 1
pcs_per_carton	integer
title: Pcs per carton
maximum: 2147483647
minimum: 0
expiration_date	string($date)
title: Expiration date
x-nullable: true
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
sku_obj	integer
title: Sku obj
x-nullable: true
mixed_carton	integer
title: Mixed carton
x-nullable: true
 
}]
c_length	string($decimal)
title: C length
c_width	string($decimal)
title: C width
c_height	string($decimal)
title: C height
c_weight	string($decimal)
title: C weight
original_qty_ctns_ordered	integer
title: Original qty ctns ordered
maximum: 2147483647
minimum: 0
actual_qty_ctns_received	integer
title: Actual qty ctns received
maximum: 2147483647
minimum: 0
net_weight_kg	string($decimal)
title: Net weight kg
carton_note	string
title: Carton note
maxLength: 300
minLength: 1
x-nullable: true
qty_cartons	integer
title: Qty cartons
maximum: 2147483647
minimum: -2147483648
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
purchase_order*	integer
title: Purchase order
 
}
Plain_Carton_Line_Item_{
description:	
Plain_Carton_Line_Item(id, organization, owner, sku_obj, sku, pcs_per_carton, landed_cost_per_pc_EUR, purchase_price_per_pc, expiration_date, purchase_order, qty_cartons, cartons_left_cached, c_length, c_width, c_height, c_weight, to_be_printed_on_Label, qty_cartons_not_found_before_send_in, original_qty_ctns_ordered, actual_qty_ctns_received, net_weight_kg, carton_note)

id	integer
title: ID
readOnly: true
cartons_left*	integer
title: Cartons left
line_items*	[string]
is_loose_stock*	boolean
title: Is loose stock
qty_cartons	integer
title: Qty cartons
maximum: 2147483647
minimum: 0
c_length	string($decimal)
title: C length
c_width	string($decimal)
title: C width
c_height	string($decimal)
title: C height
c_weight	string($decimal)
title: C weight
original_qty_ctns_ordered	integer
title: Original qty ctns ordered
maximum: 2147483647
minimum: 0
actual_qty_ctns_received	integer
title: Actual qty ctns received
maximum: 2147483647
minimum: 0
net_weight_kg	string($decimal)
title: Net weight kg
carton_note	string
title: Carton note
maxLength: 300
minLength: 1
x-nullable: true
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
purchase_order*	integer
title: Purchase order
 
}
Purchase_Order_Incl_Line_Items_{
description:	
Purchase_Order(id, organization, owner, order_name, order_placed_date, order_shipped_date, order_received_date, date_added, supplier, supplier_address, billing_address, ship_to_address, po_number, departure_port, inco_term, payment_terms, additional_agreements, purchase_currency, order_notes, status, archived, is_loose_stock_to_carton_conversion, is_bundle_dissolving, is_bundle_assembly, loose_stock_conversion_sourcing_plan, warehouse_transfer_fba_send_in_source_id, warehouse, agl_shipment_link, display_type, transport_mode, category, custom_status, virtual_po)

id	integer
title: ID
readOnly: true
mixed_cartons	[
readOnly: true
Mixed_Carton_{
description:	
Mixed_Carton(id, organization, owner, purchase_order, cartons_left_cached, c_length, c_width, c_height, c_weight, to_be_printed_on_Label, original_qty_ctns_ordered, actual_qty_ctns_received, net_weight_kg, carton_note, qty_cartons, qty_cartons_not_found_before_send_in)

id	integer
title: ID
readOnly: true
cartons_left*	integer
title: Cartons left
line_items	[...]
c_length	string($decimal)
title: C length
c_width	string($decimal)
title: C width
c_height	string($decimal)
title: C height
c_weight	string($decimal)
title: C weight
original_qty_ctns_ordered	integer
title: Original qty ctns ordered
maximum: 2147483647
minimum: 0
actual_qty_ctns_received	integer
title: Actual qty ctns received
maximum: 2147483647
minimum: 0
net_weight_kg	string($decimal)
title: Net weight kg
carton_note	string
title: Carton note
maxLength: 300
minLength: 1
x-nullable: true
qty_cartons	integer
title: Qty cartons
maximum: 2147483647
minimum: -2147483648
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
purchase_order*	integer
title: Purchase order
 
}]
plain_cartons	[
readOnly: true
Plain_Carton_Line_Item_{
description:	
Plain_Carton_Line_Item(id, organization, owner, sku_obj, sku, pcs_per_carton, landed_cost_per_pc_EUR, purchase_price_per_pc, expiration_date, purchase_order, qty_cartons, cartons_left_cached, c_length, c_width, c_height, c_weight, to_be_printed_on_Label, qty_cartons_not_found_before_send_in, original_qty_ctns_ordered, actual_qty_ctns_received, net_weight_kg, carton_note)

id	integer
title: ID
readOnly: true
cartons_left*	integer
title: Cartons left
line_items*	[...]
is_loose_stock*	boolean
title: Is loose stock
qty_cartons	integer
title: Qty cartons
maximum: 2147483647
minimum: 0
c_length	string($decimal)
title: C length
c_width	string($decimal)
title: C width
c_height	string($decimal)
title: C height
c_weight	string($decimal)
title: C weight
original_qty_ctns_ordered	integer
title: Original qty ctns ordered
maximum: 2147483647
minimum: 0
actual_qty_ctns_received	integer
title: Actual qty ctns received
maximum: 2147483647
minimum: 0
net_weight_kg	string($decimal)
title: Net weight kg
carton_note	string
title: Carton note
maxLength: 300
minLength: 1
x-nullable: true
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
purchase_order*	integer
title: Purchase order
 
}]
order_name*	string
title: Order name
maxLength: 300
minLength: 1
order_placed_date	string($date)
title: Order placed date
x-nullable: true
order_shipped_date	string($date)
title: Order shipped date
x-nullable: true
order_received_date	string($date)
title: Order received date
x-nullable: true
date_added	string($date-time)
title: Date added
readOnly: true
supplier_address	string
title: Supplier address
maxLength: 300
minLength: 1
x-nullable: true
billing_address	string
title: Billing address
maxLength: 300
minLength: 1
x-nullable: true
ship_to_address	string
title: Ship to address
maxLength: 300
minLength: 1
x-nullable: true
po_number	string
title: Po number
maxLength: 300
minLength: 1
x-nullable: true
departure_port	string
title: Departure port
maxLength: 300
minLength: 1
x-nullable: true
inco_term	string
title: Inco term
x-nullable: true
Enum:
Array [ 13 ]
payment_terms	string
title: Payment terms
maxLength: 1000
minLength: 1
x-nullable: true
additional_agreements	string
title: Additional agreements
maxLength: 10000
minLength: 1
x-nullable: true
purchase_currency	string
title: Purchase currency
maxLength: 20
minLength: 1
order_notes	string
title: Order notes
maxLength: 2000
status	string
title: Status
Enum:
Array [ 4 ]
archived	boolean
title: Archived
is_loose_stock_to_carton_conversion	boolean
title: Is loose stock to carton conversion
is_bundle_dissolving	boolean
title: Is bundle dissolving
is_bundle_assembly	boolean
title: Is bundle assembly
transport_mode	string
title: Transport mode
Enum:
Array [ 6 ]
category	string
title: Category
Enum:
Array [ 5 ]
custom_status	string
title: Custom status
maxLength: 50
x-nullable: true
virtual_po	boolean
title: Virtual po
organization*	integer
title: Organization
owner	integer
title: Owner
x-nullable: true
supplier	integer
title: Supplier
x-nullable: true
warehouse	integer
title: Warehouse
x-nullable: true
agl_shipment_link	integer
title: Agl shipment link
x-nullable: true
 
}
SKU{
description:	
SKU(id, organization, owner, status, forecasting_enabled, auto_send_in_suggestion, amz_fulfillment_type, amz_product_type_name, amz_product_group, amz_product_binding, sku, ASIN, Parent_ASIN, FNSKU, small_image_url, man_small_image_url, stock_by_warehouse_cached, wh_pcs_left_cached, pcs_on_the_way_cached, bundle_wh_pcs_left_cached, bundle_pcs_on_the_way_cached, amz_condition, synced_with_sku_obj, aggregated_to_parent_sku, is_bundle_parent_sku, is_bundle_child_sku, to_be_notified_email, custom_product_id, def_product_name_for_invoice, def_purchase_price, moneyback_purchase_price, def_landed_cost_EUR, avg_landed_cost_EUR_cached, avg_landed_cost_at_fba_EUR_cached, avg_landed_cost_in_stock_EUR_cached, avg_landed_cost_on_the_way_EUR_cached, date_updated_fba_data, fba_oos_history_adjustment_enabled, amz_per_unit_volume, afn_inbound_manual_correction_quantity, afn_inbound_pcs_on_hold, source_amazon_de, source_amazon_fr, source_amazon_it, source_amazon_es, source_amazon_co_uk, source_shopify, source_ebay, amz_selling_price_EUR, amz_product_id, Color, Size, date_updated_parent_child_relationships, cat_product_type, cat_color, cat_size, cat_shape, overview_category, max_allowed_send_in_pcs, target_reach_fba_in_days, fbm_target_reach_in_days, target_reach_total_in_days, lead_time_fba_in_days, fbm_lead_time_in_days, lead_time_re_order_in_days, replenishment_frequency_fba_in_days, fbm_replenishment_frequency_in_days, replenishment_frequency_re_order_in_days, sales_last_30_days_manual, min_fba_stock, min_fbm_stock, fba_send_in_priority_asc, moq, re_order_logic, weighting_sales_last_3_days, weighting_sales_last_7_days, weighting_sales_last_30_days, weighting_sales_last_90_days, weighting_sales_last_180_days, weighting_sales_last_365_days, weighting_sales_last_180_to_365_days, re_order_weighting_sales_last_3_days, re_order_weighting_sales_last_7_days, re_order_weighting_sales_last_30_days, re_order_weighting_sales_last_90_days, re_order_weighting_sales_last_180_days, re_order_weighting_sales_last_365_days, re_order_weighting_sales_last_180_to_365_days, oos_days_last_3_days, oos_days_last_7_days, oos_days_last_30_days, oos_days_last_90_days, oos_days_last_180_days, oos_days_last_365_days, oos_days_last_180_to_365_days, date_updated_sales_data, amz_listing_status, amz_title, amz_sync_status, amz_length_in_cm, amz_width_in_cm, amz_height_in_cm, amz_weight_in_kg, man_length_in_cm, man_width_in_cm, man_height_in_cm, man_weight_in_kg, def_pcs_per_carton, def_c_length, def_c_width, def_c_height, def_c_weight, def_amz_prep_instructions_labelling, def_amz_prep_instructions_preparation, def_additional_amz_prep_instructions, notes, use_custom_measurements, amazon_product_packaging, avg_price, avg_profitability, avg_fees, seasonal_forecasting_enabled, seasonal_coefficients, seasonal_forecasting_fbm_enabled, seasonal_coefficients_fbm, specific_display_name, specific_classification_id, top_level_display_name, top_level_classification_id)

id	integer
title: ID
readOnly: true
status	string
title: Status
Enum:
Array [ 3 ]
amz_fulfillment_type	string
title: Amz fulfillment type
x-nullable: true
Enum:
Array [ 2 ]
sku*	string
title: Sku
maxLength: 300
minLength: 1
ASIN	string
title: ASIN
maxLength: 300
minLength: 1
x-nullable: true
Parent_ASIN	string
title: Parent ASIN
maxLength: 300
minLength: 1
x-nullable: true
FNSKU	string
title: FNSKU
maxLength: 300
minLength: 1
x-nullable: true
small_image_url	string
title: Small image url
minLength: 1
x-nullable: true
man_small_image_url	string
title: Man small image url
x-nullable: true
to_be_notified_email	string
title: To be notified email
maxLength: 500
custom_product_id	string
title: Custom product id
maxLength: 300
def_product_name_for_invoice	string
title: Def product name for invoice
maxLength: 300
amz_per_unit_volume	number
title: Amz per unit volume
x-nullable: true
afn_inbound_manual_correction_quantity	integer
title: Afn inbound manual correction quantity
maximum: 2147483647
minimum: -2147483648
x-nullable: true
source_amazon_de	string
title: Source amazon de
Enum:
Array [ 8 ]
source_amazon_fr	string
title: Source amazon fr
Enum:
Array [ 8 ]
source_amazon_it	string
title: Source amazon it
Enum:
Array [ 8 ]
source_amazon_es	string
title: Source amazon es
Enum:
Array [ 8 ]
source_amazon_co_uk	string
title: Source amazon co uk
Enum:
Array [ 8 ]
amz_selling_price_EUR	string($decimal)
title: Amz selling price EUR
amz_product_id	string
title: Amz product id
maxLength: 300
cat_product_type	string
title: Cat product type
maxLength: 300
x-nullable: true
cat_color	string
title: Cat color
maxLength: 300
x-nullable: true
cat_size	string
title: Cat size
maxLength: 300
x-nullable: true
cat_shape	string
title: Cat shape
maxLength: 300
x-nullable: true
overview_category	string
title: Overview category
maxLength: 300
target_reach_fba_in_days	integer
title: Target reach fba in days
maximum: 2147483647
minimum: -2147483648
target_reach_total_in_days	integer
title: Target reach total in days
maximum: 2147483647
minimum: -2147483648
lead_time_fba_in_days	integer
title: Lead time fba in days
maximum: 2147483647
minimum: -2147483648
lead_time_re_order_in_days	integer
title: Lead time re order in days
maximum: 2147483647
minimum: -2147483648
replenishment_frequency_fba_in_days	integer
title: Replenishment frequency fba in days
maximum: 2147483647
minimum: -2147483648
replenishment_frequency_re_order_in_days	integer
title: Replenishment frequency re order in days
maximum: 2147483647
minimum: -2147483648
sales_last_30_days_manual	integer
title: Sales last 30 days manual
maximum: 2147483647
minimum: -2147483648
min_fba_stock	integer
title: Min fba stock
maximum: 2147483647
minimum: -2147483648
fba_send_in_priority_asc	integer
title: Fba send in priority asc
maximum: 2147483647
minimum: -2147483648
moq	integer
title: Moq
maximum: 2147483647
minimum: 0
re_order_logic	string
title: Re order logic
Enum:
Array [ 2 ]
weighting_sales_last_3_days	number
title: Weighting sales last 3 days
weighting_sales_last_7_days	number
title: Weighting sales last 7 days
weighting_sales_last_30_days	number
title: Weighting sales last 30 days
weighting_sales_last_90_days	number
title: Weighting sales last 90 days
weighting_sales_last_180_days	number
title: Weighting sales last 180 days
weighting_sales_last_365_days	number
title: Weighting sales last 365 days
amz_title	string
title: Amz title
minLength: 1
x-nullable: true
amz_length_in_cm	number
title: Amz length in cm
x-nullable: true
amz_width_in_cm	number
title: Amz width in cm
x-nullable: true
amz_height_in_cm	number
title: Amz height in cm
x-nullable: true
amz_weight_in_kg	number
title: Amz weight in kg
x-nullable: true
man_length_in_cm	number
title: Man length in cm
x-nullable: true
man_width_in_cm	number
title: Man width in cm
x-nullable: true
man_height_in_cm	number
title: Man height in cm
x-nullable: true
man_weight_in_kg	number
title: Man weight in kg
x-nullable: true
def_pcs_per_carton	integer
title: Def pcs per carton
maximum: 2147483647
minimum: -2147483648
x-nullable: true
def_c_length	string($decimal)
title: Def c length
x-nullable: true
def_c_width	string($decimal)
title: Def c width
x-nullable: true
def_c_height	string($decimal)
title: Def c height
x-nullable: true
def_c_weight	string($decimal)
title: Def c weight
x-nullable: true
def_amz_prep_instructions_labelling	string
title: Def amz prep instructions labelling
Enum:
Array [ 3 ]
organization*	integer
title: Organization
re_order_template	[
uniqueItems: true
integer]
def_landed_cost_EUR	string($decimal)
title: Def landed cost EUR
def_purchase_price	string($decimal)
title: Def purchase price
 
}
