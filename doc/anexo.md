# Anexo

## Constantes
|Constante|Descripción|
|--------:|:----------|
|`Facturae::SCHEMA_3_2`|Formato de Facturae 3.2|
|`Facturae::SCHEMA_3_2_1`|Formato de Facturae 3.2.1|
|`Facturae::SCHEMA_3_2_2`|Formato de Facturae 3.2.2|
|`Facturae::SIGN_POLICY_3_1`|Formato de firma 3.1|

|Constante|Descripción|
|--------:|:----------|
|`Facturae::PAYMENT_CASH`|Pago al contado|
|`Facturae::PAYMENT_TRANSFER`|Pago por transferencia|

|Constante|Descripción|
|--------:|:----------|
|`Facturae::TAX_IVA`|Impuesto sobre el valor añadido|
|`Facturae::TAX_IPSI`|Impuesto sobre la producción, los servicios y la importación|
|`Facturae::TAX_IGIC`|Impuesto general indirecto de Canarias|
|`Facturae::TAX_IRPF`|Impuesto sobre la Renta de las personas físicas|
|`Facturae::TAX_OTHER`|Otro|
|`Facturae::TAX_ITPAJD`|Impuesto sobre transmisiones patrimoniales y actos jurídicos documentados|
|`Facturae::TAX_IE`|Impuestos especiales|
|`Facturae::TAX_RA`|Renta aduanas|
|`Facturae::TAX_IGTECM`|Impuesto general sobre el tráfico de empresas que se aplica en Ceuta y Melilla|
|`Facturae::TAX_IECDPCAC`|Impuesto especial sobre los combustibles derivados del petróleo en la Comunidad Autonoma Canaria|
|`Facturae::TAX_IIIMAB`|Impuesto sobre las instalaciones que inciden sobre el medio ambiente en la Baleares|
|`Facturae::TAX_ICIO`|Impuesto sobre las construcciones, instalaciones y obras|
|`Facturae::TAX_IMVDN`|Impuesto municipal sobre las viviendas desocupadas en Navarra|
|`Facturae::TAX_IMSN`|Impuesto municipal sobre solares en Navarra|
|`Facturae::TAX_IMGSN`|Impuesto municipal sobre gastos suntuarios en Navarra|
|`Facturae::TAX_IMPN`|Impuesto municipal sobre publicidad en Navarra|
|`Facturae::TAX_REIVA`|Régimen especial de IVA para agencias de viajes|
|`Facturae::TAX_REIGIC`|Régimen especial de IGIC: para agencias de viajes|
|`Facturae::TAX_REIPSI`|Régimen especial de IPSI para agencias de viajes|

|Constante|Descripción|
|--------:|:----------|
|`FacturaeCentre::ROLE_CONTABLE`<br>o `FacturaeCentre::ROLE_FISCAL`|Oficina contable|
|`FacturaeCentre::ROLE_GESTOR`<br>o `FacturaeCentre::ROLE_RECEPTOR`|Órgano gestor|
|`FacturaeCentre::ROLE_TRAMITADOR`<br>o `FacturaeCentre::ROLE_PAGADOR`|Unidad tramitadora|
|`FacturaeCentre::ROLE_PROPONENTE`|Órgano proponente|

## Herramientas de validación
Todas las facturas generadas y firmadas con Facturae-PHP son probadas con las siguientes herramientas online para garantizar el cumplimiento del estándar:

- https://viewer.facturadirecta.com/ (no soporta 3.2.2)
- http://plataforma.firma-e.com/VisualizadorFacturae/ (no soporta 3.2.2)
- http://sedeaplicaciones2.minetur.gob.es/FacturaE/index.jsp
- https://face.gob.es/es/facturas/validar-visualizar-facturas
