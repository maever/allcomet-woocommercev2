**Api Document**

**V2.0.0**

**2020-08-19**

**I. Interface specification** **3**

**II. Symbolic conventions** **3**

**III. Interface list** **3**

**1\. Payment** **3**

1.1. Request URL 3

1.2. Request Parameters List 4

1.3. Response results 6

**2\. Notify** **7**

2.1. Response results 7

**3\. Query** **8**

3.1. Request URL 8

3.2. Request Parameters List 8

3.3. Response results 8

**4\. Refund** **9**

4.1. Request URL 9

4.2. Request Parameters List 9

4.3. Response results 9

**5\. Charge Back Asynchronous Push** **10**

**6\. Appendix1** **11**

**7\. Code** **12**

**Test Merchant Info：**

| Request URL： | <https://api.thelinemall.com/apiv2/pay> |
| --- | --- |
| MID： | xxxxxxx |
| MD5-key： | xxxxxx |
| Card Type | V/M/AE/JCB/DC |

# Interface specification

1. Api use https to access
2. Submit method is POST
3. Content-Type is application/x-www-form-urlencoded
4. Encoded as UTF-8
5. Both the submitted data and the returned data need to verify the signature
6. The signature method is MD5
7. The payment interface signature is sorted by ASCII code with all parameters from small to large, concatenated using the URL key-value pair format, then concatenated with &key=MD5Key, and finally encrypted with MD5, for example (key1=value1&key2=value2&key=md5key)

# Symbolic conventions

| Symbol | Descriptions |
| --- | --- |
| M   | Must include |
| C   | If conditions are met, they must include |
| O   | Optional |

# Interface list

## Payment

### Request URL

<https://xx.xx.com/apiv2/pay>

### Request Parameters List

| Field Name | Processing | Descriptions |
| --- | --- | --- |
| merNo | M   | Merchant number |
| amount | M   | Accurate to 2 decimal places. |
| billNo | M   | A string of unique numbers, not more than 30 length |
| currency | M   | See Appendix1 and use Currency number |
| returnURL | M   | Return address after payment is completed |
| notifyUrl | M   | Accept asynchronous notification url address<br><br>.Must be accessible from extranet |
| tradeUrl | M   | Trading website |
| lastName | M   | Length should not be greater than 30 |
| firstName | M   | Length should not be greater than 60 |
| country | M   | Up to 2 characters, using two characters in the ISO-3166-1 standard |
| state | M   |     |
| city | M   |     |
| address | M   |     |
| zipCode | M   |     |
| email | M   |     |
| phone | M   |     |
| cardNum | M   |     |
| year | M   | Length should be 4 |
| month | M   | Length should be 2 |
| cvv2 | M   | Length should be 3 or 4 |
| productInfo | M   | Length should not be greater than 4000(json string) |
| md5Info | M   | Refer to 1.6 |
| ip  | M   | User ip |
| dataTime | M   | 20200601140122 |
| shippingFirstName | M   |     |
| shippingLastName | M   |     |
| shippingCountry | M   |     |
| shippingState | M   |     |
| shippingCity | M   |     |
| shippingAddress | M   |     |
| shippingZipCode | M   |     |
| shippingEmail | M   |     |
| shippingPhone | M   |     |
| isThreeDPay | M   | Optional parameters is Y、N，It’s 3DS |
| language | O   | EN、ZH_CN |

### Response results

| Field Name | Processing | Descriptions |
| --- | --- | --- |
| code | M   | Q0001：request success<br><br>P0001：payment success<br><br>P0002：failed |
| message | M   |     |
| orderNo | M   |     |
| merNo | M   |     |
| billNo | M   |     |
| amount | M   |     |
| currency | M   |     |
| returnURL | M   | Return address after payment is completed |
| tradeTime | M   |     |
| md5Info | M   | Refer to 1.6 |
| auth3DUrl | M   | 3D Authentication page |
| billAddr | M   | Billing descriptor |

## Notify

### Response results

| Field Name | Processing | Descriptions |
| --- | --- | --- |
| code | M   | P0001:Payment Success<br><br>P0002：Payment failed |
| message | M   | message |
| rand | M   | The random number |
| currency | M   |     |
| orderNo | M   |     |
| tradeTime | M   |     |
| time | M   |     |
| merNo | M   |     |
| billNo | M   |     |
| amount | M   |     |
| md5Info | M   | Refer to 1.6 |
| billAddr | M   | Billing descriptor |

## Query

### Request URL

**https://**xx.xx.com**/apiv2/query**

### Request Parameters List

| Field Name | Processing | Descriptions |
| --- | --- | --- |
| merNo | M   |     |
| billNo | M   |     |
| md5Info | M   | Refer to 1.6 |

### Response results

| Field Name | Processing | Descriptions |
| --- | --- | --- |
| code | M   | S0001：Payment Successful<br><br>S0002：Payment failed |
| message | M   |     |
| orderNo | M   |     |
| merNo | M   |     |
| billNo | M   |     |
| amount | M   |     |
| md5Info | M   | Refer to 1.6 |

## Refund

### Request URL

**https://**xx.xx.com**/apiv2/refund**

### Request Parameters List

| Field Name | Processing | Descriptions |
| --- | --- | --- |
| merNo | M   |     |
| amount | M   | Accurate to 2 decimal places. |
| orderNo | M   | order number |
| remark | M   |     |
| md5Info | M   | Refer to 1.6 |

### Response results

| Field Name | Processing | Descriptions |
| --- | --- | --- |
| code | M   | T0001：refund appley success |
| message | M   |     |
| orderNo | M   |     |
| merNo | M   |     |
| amount | M   |     |
| md5Info | M   | Refer to 1.6 |

## Charge Back Asynchronous Push

- Please provide an URL receiving CB asynchronous notification to our operation team to set up firstly.
- When CB is generated, our system will send CB notification to the asynchronous notify URL you provided.
- Return to "SUCCESS" after getting the push information, otherwise the push will be repeated five times.

| Field Name | Processing | Descriptions |
| --- | --- | --- |
| merNo | M   | MID |
| merName | M   | Merchant Name |
| orderNo | M   | System order number |
| billNo | M   |     |
| amount | M   | Chargeback amount |
| currency | M   |     |
| reason | M   |     |
| date | M   | Chargeback time |
| type | M   | Full Amount CB<br><br>Partial CB<br><br>Refund turn to CB |
| md5Info | M   | Refer to 1.6 |

## Appendix1

| currency | Currency coding | Currency number |
| --- | --- | --- |
| Dollar | USD | 1   |
| Euro | EUR | 2   |
| RMB | RMB | 3   |
| Pound | GBP | 4   |
| HongKong dollar | HKD | 5   |
| Yen | JPY | 6   |
| Australian dollar | AUD | 7   |
| Norway kroner | NOK | 8   |
| Cad | CAD | 11  |
| Dkk | DKK | 12  |
| Swedish Krona | SEK | 13  |
| New Taiwan Currency | TWD | 14  |

## Code

| return code | Descriptions |
| --- | --- |
| P0001 | Payment successful |
| Q0001 | Request success |
| P0002 | Payment failed |
| A0001 | Param merNo can not be empty |
| A0002 | Param merNo Format error |
| A0003 | Param amount can not be empty |
| A0004 | Param amount Format error |
| A0005 | Param product Info can not be empty |
| A0006 | Param product Info Length exceeding limit |
| A0007 | Param currencycan not be empty |
| A0008 | Param currencyFormat error |
| A0009 | Param cardNumcan not be empty |
| A0010 | Param cardNumFormat error |
| A0011 | Param year can not be empty |
| A0012 | Param year Format error |
| A0013 | Param month can not be empty |
| A0014 | Param month Format error |
| A0015 | Please check if the credit card validity is correct' |
| A0016 | Param cvv2 can not be empty |
| A0017 | Param cvv2 Format error |
| A0018 | Param firstName can not be empty |
| A0019 | Param firstName Format error |
| A0020 | Param lastNamecan not be empty |
| A0021 | Param lastNameFormat error |
| A0022 | Param addresscan not be empty |
| A0023 | Param citycan not be empty |
| A0024 | Param countrycan not be empty |
| A0025 | Param zipCodecan not be empty |
| A0026 | Param emailcan not be empty |
| A0027 | Param emailFormat error |
| A0028 | Param phonecan not be empty |
| A0029 | Param phoneFormat error |
| A0039 | Param returnURL can not be empty |
| A0041 | Param billNo can not be empty |
| A0042 | Param billNo Format error |
| A0043 | Param billNo repeat |
| A0044 | Please check if Merchant number is correct |
| A0045 | Please contact operation team to check MID status |
| A0046 | Payment currency error |
| A0047 | Failure to verify signature |
| A0048 | Channel not open |
| A0050 | Param ip can not be empty |
| A0051 | MID not activated.Please contact operation team to check MID status |
| A0052 | The card type is not supported by our system, please use correct card type |
| A0102 | Param md5Info can not be empty |
| A0103 | Param notifyUrl can not be empty |
| A0104 | Param tradeUrl can not be empty |
| A0105 | Param state can not be empty |
| A0106 | Param dataTime can not be empty |
| A0107 | Param dataTime Format error |
| A0108 | Param firstName Format error |
| R0002 | Transaction amount  excess daily limit |
| R0003 | Black card |
| R0004 | Black Bin |
| R0005 | Black email |
| R0006 | Black IP |
| R0007 | Merchant Black card |
| R0008 | Merchant Black email |
| R0009 | Merchant Black IP |
| R0010 | The times of successful transactions with the same card number at one merchant exceeds the daily limit |
| R0011 | The times of successful transactions with the same email address at one merchant exceeds the daily limit |
| R0012 | The times of successful transactions with the same cell phone number at one merchant exceeds the daily limit |
| R0013 | The times of successful transactions with the same IP at one merchant exceeds the daily limit |
| R0014 | The times of failed transactions with the same card number exceeds the limit in a certain time period |
| R0015 | The times of transactions with the same card number exceeds the limit within a certain time period |
| R0016 | The times of failed transactions with the same email address exceeds the limit in a certain time period |
| R0017 | The times of transactions with the same email address exceeds the limit within a certain time period |
| R0018 | The times of failed transactions with the same cell phone number exceeds the limit in a certain time period |
| R0019 | The times of transactions with the same cell phone number exceeds the limit in a certain time period |
| R0020 | The times of failed transactions with the same IP exceeds the limit in a certain time period |
| R0021 | The times of transactions with the same IP exceeds the limit in a certain time period |
| R0022 | Site nonactivated |
| R0023 | The times of successful transactions with the same card number at one merchant exceeded the limit |
| R0024 | The times of successful transactions with the same email address at one merchant exceeds the limit |
| R0025 | The times of cards with same email used at different merchant exceeds the limit |
| R0026 | Payment amount exceeds the higher limit |
| R0027 | Payment amount less than the lower limit of amount |
| R0028 | High-risk Trading |
| R0029 | The number of terminal transactions exceeds the limit |
| R0033 | National restriction (time period) |
| R0039 | Not in the card number whitelist |
| R0040 | Not in the mailbox whitelist |
| R0043 | The times of requests initiated by the merchant per second exceeds the limit |
| R0046 | The same cardNum request exceeds the limit in a certain time period |
| R0047 | The cardNum of successful transactions of the same card number in a certain time period exceeds the limit |
| R0048 | The cardNum of failed transactions of the same card number in a certain time period exceeds the limit |
| R0049 | IP not activated. Please contact operation team to set up IP whitelist |
| B0001 | System busy |
| B0002 | System error |
| T0001 | refund application success |
| T0002 | refund application failed (Only successful transactions can be initiated refund application) |
| S0001 | query order payment Successful |
| S0002 | query order payment failed |
| S0003 | query order not exist |
| S0005 | Refund Failed |
| S0007 | Refunded |
| S0008 | Refunding |
| S0009 | Total Refund/CB amount can not greater than original TXN amount |
| S0010 | Refund cancel |
| S0011 | Refund to charge back |
| S0012 | Charge back |
