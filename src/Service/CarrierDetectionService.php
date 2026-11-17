<?php
namespace Starbug\ShippingMethods\Service;

class CarrierDetectionService {
  private static $carriers = [
      // UPS
      [
          'carrier' => 'UPS',
          'url' => 'http://wwwapps.ups.com/WebTracking/processInputRequest?TypeOfInquiryNumber=T&InquiryNumber1=',
          'reg' => '/\b(1Z ?[0-9A-Z]{3} ?[0-9A-Z]{3} ?[0-9A-Z]{2} ?[0-9A-Z]{4} ?[0-9A-Z]{3} ?[0-9A-Z]|T\d{3} ?\d{4} ?\d{3})\b/i'
      ],
      // FedEx
      [
          'carrier' => 'FedEx',
          'url' => 'https://www.fedex.com/fedextrack/?trknbr=',
          'reg' => '/\b(((96\d\d|6\d)\d{3} ?\d{4}|96\d{2}|\d{4}) ?\d{4} ?\d{4}( ?\d{3})?)\b/i'
      ],
      // USPS - Format 1
      [
          'carrier' => 'USPS',
          'url' => 'https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=',
          'reg' => '/\b((420 ?\d{5} ?)?(91|92|93|94|95|01|03|04|70|23|13)\d{2} ?\d{4} ?\d{4} ?\d{4} ?\d{4}( ?\d{2,6})?)\b/i'
      ],
      // USPS - Format 2 (International alphanumeric)
      [
          'carrier' => 'USPS',
          'url' => 'https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=',
          'reg' => '/\b((M|P[A-Z]?|D[C-Z]|LK|E[A-C]|V[A-Z]|R[A-Z]|CP|CJ|LC|LJ) ?\d{3} ?\d{3} ?\d{3} ?[A-Z]?[A-Z]?)\b/i'
      ],
      // USPS - Format 3 (Smaller numeric codes)
      [
          'carrier' => 'USPS',
          'url' => 'https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=',
          'reg' => '/\b(82 ?\d{3} ?\d{3} ?\d{2})\b/i'
      ],
      // OnTrac
      [
          'carrier' => 'OnTrac',
          'url' => 'http://www.ontrac.com/trackres.asp?tracking_number=',
          'reg' => '/\b(C\d{14})\b/i'
      ],
      // DHL
      [
          'carrier' => 'DHL',
          'url' => 'http://www.dhl.com/content/g0/en/express/tracking.shtml?brand=DHL&AWB=',
          'reg' => '/\b(\d{4}[- ]?\d{4}[- ]?\d{2}|\d{3}[- ]?\d{8}|[A-Z]{3}\d{7})\b/i'
      ],
      // Add more carriers here as needed
  ];

  /**
   * Detect the carrier based on the tracking number and return the tracking URL.
   *
   * @param string $tracking_number The tracking number to detect.
   *
   * @return array|null The carrier information with URL or null if no match is found.
   */
  public function detectCarrier(string $tracking_number): ?array {
    foreach (self::$carriers as $carrier) {
      if (preg_match($carrier['reg'], $tracking_number)) {
          return [
              'carrier' => $carrier['carrier'],
              'url' => $carrier['url'] . $tracking_number
          ];
      }
    }

    return null; // Return null if no carrier matches the tracking number.
  }
}
