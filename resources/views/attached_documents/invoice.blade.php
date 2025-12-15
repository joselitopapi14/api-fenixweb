<?xml version="1.0" encoding="utf-8" standalone="no"?>
<AttachedDocument xmlns="urn:oasis:names:specification:ubl:schema:xsd:AttachedDocument-2"
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
    xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
    xmlns:ccts="urn:un:unece:uncefact:data:specification:CoreComponentTypeSchemaModule:2"
    xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
    xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#">
    <cbc:UBLVersionID>UBL 2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>Documentos adjuntos</cbc:CustomizationID>
    <cbc:ProfileID>{{$factura->tipoFactura->name ?? 'Factura'}}</cbc:ProfileID>
    <cbc:ProfileExecutionID>{{$empresa->tipo_ambiente ?? '2'}}</cbc:ProfileExecutionID>
    <cbc:ID>{{$factura->id}}</cbc:ID>
    <cbc:IssueDate>{{ \Carbon\Carbon::parse($factura->updated_at)->setTimezone('-05:00')->format('Y-m-d') }}</cbc:IssueDate>
    <cbc:IssueTime>{{ \Carbon\Carbon::parse($factura->updated_at)->setTimezone('-05:00')->format('H:i:s') }}</cbc:IssueTime>
    <cbc:DocumentType>Contenedor de Factura Electrónica</cbc:DocumentType>
    <cbc:ParentDocumentID>{{$factura->numero_factura}}</cbc:ParentDocumentID>
    <cac:SenderParty>
        <cac:PartyTaxScheme>
            <cbc:RegistrationName>{{ $empresa->razon_social }}</cbc:RegistrationName>
            <cbc:CompanyID schemeName='{{$empresa->tipoDocumento->code ?? '31'}}' schemeID='{{$empresa->dv}}'
                schemeAgencyName='CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)' schemeAgencyID='195'>{{$empresa->nit}}</cbc:CompanyID>
            <cbc:TaxLevelCode>{{$empresa->tipoResponsabilidad->code ?? 'O-13'}}</cbc:TaxLevelCode>
            <cac:TaxScheme>
                <cbc:ID>01</cbc:ID>
                <cbc:Name>IVA</cbc:Name>
            </cac:TaxScheme>
        </cac:PartyTaxScheme>
    </cac:SenderParty>
    <cac:ReceiverParty>
        <cac:PartyTaxScheme>
            <cbc:RegistrationName>{{$cliente->nombre_completo}}</cbc:RegistrationName>
            <cbc:CompanyID schemeName='{{$cliente->tipoDocumento->code ?? 'CC'}}' schemeID='{{$cliente->dv ?? ''}}'
                schemeAgencyName='CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)' schemeAgencyID='195'>
                {{$cliente->cedula_nit}}</cbc:CompanyID>
            <cbc:TaxLevelCode listName='48'>{{$cliente->tipoResponsabilidad->code ?? 'R-99-PN'}}</cbc:TaxLevelCode>
            <cac:TaxScheme />
        </cac:PartyTaxScheme>
    </cac:ReceiverParty>
    <cac:Attachment>
        <cac:ExternalReference>
            <cbc:MimeCode>text/xml</cbc:MimeCode>
            <cbc:EncodingCode>UTF-8</cbc:EncodingCode>
            <cbc:Description>
                <![CDATA[{{$documentXMl}}]]>
            </cbc:Description>
        </cac:ExternalReference>
    </cac:Attachment>
    <cac:ParentDocumentLineReference>
        <cbc:LineID>1</cbc:LineID>
        <cac:DocumentReference>
            <cbc:ID>{{$factura->numero_factura}}</cbc:ID>
            <cbc:UUID schemeName='CUFE-SHA384'>{{$factura->cufe}}</cbc:UUID>
            <cbc:IssueDate>{{ \Carbon\Carbon::parse($factura->updated_at)->setTimezone('-05:00')->format('Y-m-d') }}</cbc:IssueDate>
            <cbc:DocumentType>ApplicationResponse</cbc:DocumentType>
            <cac:Attachment>
                <cac:ExternalReference>
                    <cbc:MimeCode>text/xml</cbc:MimeCode>
                    <cbc:EncodingCode>UTF-8</cbc:EncodingCode>
                    <cbc:Description>
                        <![CDATA[{!! base64_decode($respuestaDian) !!}]]>
                    </cbc:Description>
                </cac:ExternalReference>
            </cac:Attachment>
            <cac:ResultOfVerification>
                <cbc:ValidatorID>Unidad Especial Dirección de Impuestos y Aduanas Nacionales</cbc:ValidatorID>
                <cbc:ValidationResultCode>00</cbc:ValidationResultCode>
                <cbc:ValidationDate>{{ \Carbon\Carbon::parse($factura->updated_at)->setTimezone('-05:00')->format('Y-m-d') }}</cbc:ValidationDate>
                <cbc:ValidationTime>{{ \Carbon\Carbon::parse($factura->updated_at)->setTimezone('-05:00')->format('H:i:s') }}</cbc:ValidationTime>
            </cac:ResultOfVerification>
        </cac:DocumentReference>
    </cac:ParentDocumentLineReference>
</AttachedDocument>
