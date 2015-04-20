/******************************************************
*This is belonging to WCJDEMO PROJECT for connecting to SAE server
*
*Output Variables:
*int Temperture,int Humidity,boolean Windowstatus,boolean Ledstatus
*Input Variables: 
*boolean led
*
*Parts we need:
*Arduino UNO R3 or other shield like this
*Adafruit CC3000 WIFI shield(Adafruit_CC3000 library)
*DHT11 or other part like this(DHT11 library)
*Infrared sensor 
*Led
*Wires and necessary parts for connecting
*
*Version:1.0
*Written by ChuanJun Wang, 2015.04.13
*Email:wcj[at]zju.edu.cn
*
***********************************************************/
#include <Adafruit_CC3000.h>
#include <ccspi.h>
#include <SPI.h>
#include <string.h>
#include <dht11.h>
#include "utility/debug.h"

//about CC3000
#define WiDo_IRQ   3
#define WiDo_VBAT  5
#define WiDo_CS    10

Adafruit_CC3000 WiDo = Adafruit_CC3000(WiDo_CS, WiDo_IRQ, WiDo_VBAT,
SPI_CLOCK_DIVIDER); // you can change this clock speed

#define WLAN_SSID       "123"           // cannot be longer than 32 characters!
#define WLAN_PASS       "1234567890"
// Security can be WLAN_SEC_UNSEC, WLAN_SEC_WEP, WLAN_SEC_WPA or WLAN_SEC_WPA2
#define WLAN_SECURITY   WLAN_SEC_WPA2

//about dht11
#define DHTPIN 7
dht11 TEMP_AND_HUMI;

//about infrared sensor
//A0 link inf

#define LEDPIN 8

int Temperature=0;
int Humidity=0;
int Windowstatus=0;  //0 nobody 1 somebody
int Ledstatus=0;  //0 led turned off 1 led turned on

int Led=0; //0 to turn off led 1 to turn on led 
char clientString[150];
#define TOKEN           "myarduino"  //attach your own token generated from the DFRobot community website

// To get the full feature of CC3000 library from Adafruit, please comment the define command below
// #define CC3000_TINY_DRIVER    // saving the flash memory space for leonardo

#define TIMEOUT_MS      2000

void setup(){
  Serial.begin(115200);
  Serial.println(F("Hello, Wido!\n")); 

  /* Initialise the module and test the hardware connection */
  Serial.println(F("\nInitialising the CC3000 ..."));
  if (!WiDo.begin())
  {
    Serial.println(F("Unable to initialise the CC3000! Check your wiring?"));
    while(1);
  }

  /* NOTE: Secure connections are not available in 'Tiny' mode!
   By default connectToAP will retry indefinitely, however you can pass an
   optional maximum number of retries (greater than zero) as the fourth parameter.
   */
  if (!WiDo.connectToAP(WLAN_SSID,WLAN_PASS,WLAN_SECURITY)) {
    Serial.println(F("Failed!"));
    while(1);
  }

  Serial.println(F("Connected!"));

  /* Wait for DHCP to complete */
  Serial.println(F("Request DHCP"));
  while (!WiDo.checkDHCP())
  {
    delay(100); // ToDo: Insert a DHCP timeout!
  }  
}


void loop(){
  static Adafruit_CC3000_Client IoTclient;
  int DHTstatus=TEMP_AND_HUMI.read(DHTPIN);
  
  //to change Windowstatus 
  if(analogRead(A0)>=600){
    Windowstatus=1;
    Serial.println("INFPIN=HIGH");
  }else if(analogRead(A0)<=LOW){
    Windowstatus=0;
    Serial.println("INFPIN=LOW");
  }
  
    Serial.println("Read DHT11 sensor...");
    Temperature=TEMP_AND_HUMI.temperature;
    Humidity=TEMP_AND_HUMI.humidity;
    
    /* to print temperature and humidity */
    Serial.println("=====================================");
    Serial.print(":\ttemerature:");
    Serial.print(TEMP_AND_HUMI.temperature);
    Serial.print("\thumidity:");
    Serial.println(TEMP_AND_HUMI.humidity);
    Serial.println("=====================================");
 
  if(IoTclient.connected()){
    strcpy(clientString,"");
    //sprintf(clientString,"%s%d%s%d%s%d","GET /1.php?abc=",abc,"&bcd=",bcd,"&cde=",cde);
    sprintf(clientString,"%s%s%s%d%s%d%s%d%s%d","GET /arduino.php?token=",TOKEN,"&Temperature=",Temperature,"&Humidity=",Humidity,"&Windowstatus=",Windowstatus,"&Ledstatus=",Ledstatus);
    Serial.println(clientString);
    
    // attach the token to the IOT Server and Upload the sensor dataIoTclient
    IoTclient.fastrprintln(clientString);
    
    IoTclient.fastrprint(F("\r\n"));
    IoTclient.fastrprint(F("\r\n"));
    
    Serial.println();
//    Serial.println("Upload data to the IoT Server");

    /* Read data until either the connection is closed, or the idle timeout is reached. */
    unsigned long lastRead = millis();
    while (IoTclient.connected() && (millis() - lastRead < TIMEOUT_MS)) {
      while (IoTclient.available()) {
        char c = IoTclient.read();
        Serial.print(c);
        if (c == '{'){
           Led = IoTclient.read();
           Serial.println("LED set to ...");
        }
        lastRead = millis();
      }
      Serial.println();
    }
    IoTclient.close();
  }
  else{
    // Config the Host IP and DFRobot community IoT service port
    // Data Upload service PORT:  8124
    // Real time controling service PORT: 9120
    uint32_t ip = WiDo.IP2U32(120,26,103,180);
    IoTclient = WiDo.connectTCP(ip,80);
    Serial.println("Connecting IoT Server...");
  }
  //to switch led according orders
  if(Led=='0'){
    digitalWrite(LEDPIN,LOW);
    Serial.println("LEDPIN=LOW");
    Ledstatus=0;
  }else if(Led=='1'){
    digitalWrite(LEDPIN,HIGH);
    Serial.println("LEDPIN=HIGH");
    Ledstatus=1;
  }
  
  delay(1000);

}

