<?php
// Start the session to pass status messages.
session_start();

// Include database configuration
require_once 'db_config.php';
?>


<!DOCTYPE html>
<html lang="dv">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" type="text/css" href="pdf.css">
  <title>Requisition Form for Goods/Services</title>
  <style>
            @font-face {
    font-family: 'Faruma'; /* Choose a descriptive name for your font */
    src:url('fonts/Faruma.ttf') format('truetype')
    }

    body {
      font-family: "Faruma", Arial, sans-serif;
      font-size: 20px;
      width: 210mm;
      height: 297mm;
      margin-left: auto;
      margin-right: auto;
    }

    .center {
      text-align: center;
    }

    .right {
      text-align: right;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 10px;
      font-size: 16px;
    }

    td, th {
      border: 1px solid #000;
      padding: 8px;
      vertical-align: top;
    }


    .no-border {
      border: none;
    }

    .small-text {
      font-size: 12px;
    }

    .section-header {
      margin-top: 30px;
      margin-bottom: 10px;
      font-weight: bold;
    }

    .header {
      text-align: center;
      margin-bottom: 20px;
    }

    .signature-section td {
      height: 40px;
    }

    @media print {
      body {
        margin: 0;
      }
    }

p {
    text-align: center;
    font-size: 18px;
    margin-bottom: 0;
}

.dhivehi {
    font-family: 'Faruma', Arial, sans-serif;
    font-size: 18px;
    text-align: right;
    margin-top: 10px;
}
#address {
    text-align: right;
    font-size: 15px;
    margin-top: 0;
}

  </style>

</head>
<body>
  <p id="bismi">`<br>
    <img src="images/emblem.png" alt="emblem" style="width: 45px; height: auto;">
  </p>
  <div id="address">
    ށ.އަތޮޅު ހޮސްޕިޓަލް<br>ފުނަދޫ، ދިވެހިރާއްޖެ
  </div>
  <table>
    <tr>
      <td class="no-border" colspan="3">
        <strong>Office:</strong> Sh.Atoll Hospital<br>
        <strong>Section:</strong> Support Service Unit<br>
        <strong>Number:</strong> (FRM)SH-FAH-A/2025/252<br>
        <strong>Date:</strong> 20/07/2025
      </td>
    </tr>
  </table>

  <div class="header">
    <div  style="font-size: 20px;">ޚިދުމަތާއި މުދާ ލިބިގަތުމަށް އެދޭފޯމް </div>
    <div><strong>REQUISTION FORM FOR GOODS/ SERVICES</strong></div>
  </div>

          <table>
            <thead>
                <tr class="center">
                    <!-- Merged cells for Dhivehi and English headers as per image -->
                    <th>އިތުރުބަޔާން</th>
                    <th>މުދަލާ ހަވާލުވިފަރާތް</th>
                    <th>ލިބެންވީ ތާރީޚް</th>
                    <th>ތަފްޞީލް</th>
                    <th colspan="2">
                        <span>ޢަދަދު</span>
                        <span>/ Quantity</span>
                    </th>
                </tr>
                <tr class="center">
                    <th>Remarks</th>
                    <th>Recieved by</th>
                    <th>Rqt. Date</th>
                    <th>Description of Goods / Services</th>
                    <th>
                      <span>ދޫކުރި /</span><br>
                      <span>Issued</span>
                    </th>
                    <th>
                      <span>އެދުނު /</span><br>
                      <span>Requested</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <!-- Sample Row 1 -->
                <tr class="center">
                    <td data-label="Remarks">Sample Remark 1</td>
                    <td data-label="Goods Recieved by"></td>
                    <td data-label="Rqt.Date">2025-07-23</td>
                    <td data-label="Description of Goods / Services">500ml Bottled Water</td>
                    <td data-label="Issued">10</td>
                    <td data-label="Requested">12</td>
                </tr>
                <!-- Sample Row 2 -->
                <tr class="center">
                    <td data-label="Remarks">Urgent delivery required</td>
                    <td data-label="Goods Recieved by"></td>
                    <td data-label="Rqt.Date">2025-07-22</td>
                    <td data-label="Description of Goods / Services">A4 Printer Paper (Ream)</td>
                    <td data-label="Issued">2</td>
                    <td data-label="Requested">2</td>
                </tr>
                <!-- You can add more rows here -->
            </tbody>
        </table>

  <table class="signature-section">
    <thead>
      <tr class="center">
        <th> ތާރީޚް <br> Date</th>
        <th>ސޮއި <br> Signature</th>
        <th>ނަން <br> Name</th>
        <th>ކުރަންވީ ކަންތައް <br> Functions</th>
      </tr>
    </thead>
    <tbody>
      <tr class="center">
        <td>7/20/2025</td>
        <td></td>
        <td>މުޙައްމަދު ކާމިލް / އ.މެއިންޓަނަންސް އޮފިސަރ</td>
        <td> / އެދުނު<br>Requested by</td>
      </tr>
      <tr class="center">
        <td>7/20/2025</td>
        <td></td>
        <td></td>
        <td>/ ރިކުއެސްޓަށް ހުއްދަދެއްވި <br> Request Authorized by</td>
      </tr>
      <tr class="center">
        <td></td>
        <td></td>
        <td></td>
        <td>/ ދޫކުރި <br> Issued by</td>
      </tr>
    </tbody>
  </table>

</body>
</html>
